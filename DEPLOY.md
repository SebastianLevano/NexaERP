# Deploy de NexaERP a producción

Este documento describe el deploy a un VPS Ubuntu 22.04 con Nginx + PHP-FPM + MySQL. Es el camino recomendado por simplicidad y costo (un solo servidor, PyME, single-tenant).

## 1. Provisionar el servidor

- **Proveedor recomendado**: DigitalOcean, Hetzner, Contabo, o Linode
- **Specs mínimas**: 2 vCPU, 2 GB RAM, 40 GB SSD
- **OS**: Ubuntu 22.04 LTS

## 2. Instalación base

Como usuario `root`:

```bash
# Actualizar sistema
apt update && apt upgrade -y

# Crear usuario deploy
adduser deploy && usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh
# Copiar tu llave pública a /home/deploy/.ssh/authorized_keys

# Repo PHP
add-apt-repository -y ppa:ondrej/php
apt update

# Instalar stack
apt install -y nginx mysql-server \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath \
    git unzip supervisor certbot python3-certbot-nginx

# Node 20 (para build de assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

## 3. Configurar MySQL

```bash
mysql_secure_installation

mysql -uroot -p <<SQL
CREATE DATABASE nexaerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nexaerp_user'@'localhost' IDENTIFIED BY 'PASSWORD_FUERTE_AQUI';
GRANT ALL PRIVILEGES ON nexaerp.* TO 'nexaerp_user'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## 4. Clonar el proyecto

```bash
su - deploy
cd /var/www
sudo mkdir nexaerp && sudo chown deploy:deploy nexaerp
git clone <REPO_URL> nexaerp
cd nexaerp

# Instalar dependencias de producción
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# .env de producción
cp .env.example .env
php artisan key:generate
```

Edita `.env` con:

```
APP_NAME=NexaERP
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_TIMEZONE=America/Lima

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=nexaerp
DB_USERNAME=nexaerp_user
DB_PASSWORD=PASSWORD_FUERTE_AQUI

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Luego:

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AdminUserSeeder   # cambiar password del admin después
php artisan optimize
```

## 5. Nginx vhost

`/etc/nginx/sites-available/nexaerp`:

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/nexaerp/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/nexaerp /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# SSL
sudo certbot --nginx -d tu-dominio.com
```

## 6. Permisos

```bash
sudo chown -R deploy:www-data /var/www/nexaerp
sudo chmod -R 775 /var/www/nexaerp/storage /var/www/nexaerp/bootstrap/cache
```

## 7. Cron (scheduler de Laravel)

`sudo crontab -e -u deploy`:

```cron
* * * * * cd /var/www/nexaerp && php artisan schedule:run >> /dev/null 2>&1
```

Esto activa el backup automático diario (02:00).

## 8. Supervisor (queue worker)

`/etc/supervisor/conf.d/nexaerp-worker.conf`:

```ini
[program:nexaerp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/nexaerp/artisan queue:work database --tries=3 --max-time=3600
autostart=true
autorestart=true
user=deploy
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/nexaerp-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start nexaerp-worker:*
```

## 9. Procedimiento de actualización

Cada vez que mergees a `main`:

```bash
cd /var/www/nexaerp
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
sudo supervisorctl restart nexaerp-worker:*
```

## 10. Backups y restore

Los backups quedan en `/var/www/nexaerp/storage/app/backups/` (gzipped, retención 14 días).

**Backup manual**:

```bash
php artisan db:backup
```

**Restore**:

```bash
zcat storage/app/backups/nexaerp-YYYYMMDD-HHmmss.sql.gz | mysql -unexaerp_user -p nexaerp
```

**Off-site recomendado**: configurar `aws s3 sync` o `rclone` para subir los backups a S3 / R2 / Backblaze diariamente. Sin esto, perder el VPS = perder todo.

## 11. Monitoreo mínimo

- `tail -f storage/logs/laravel.log` para errores de aplicación
- `journalctl -u nginx` para errores HTTP
- Healthcheck externo (UptimeRobot, healthchecks.io) apuntando a `https://tu-dominio.com/up` (endpoint de Laravel)

## Alternativa: Laravel Forge

Si quieres saltarte casi todo el setup manual (~US$12/mes), Laravel Forge automatiza pasos 2-8. Conectas tu repo de Git y un VPS de DO/Hetzner/Vultr y queda corriendo en 10 minutos.

## Lo que NO se necesita

- Docker (puro overhead para single-tenant)
- Kubernetes / Swarm
- CDN (assets se sirven directo de Nginx, son pequeños tras minify)
- Load balancer (un solo nodo basta para una PyME)
