# NexaERP

[![CI](https://github.com/SebastianLevano/NexaERP/actions/workflows/ci.yml/badge.svg)](https://github.com/SebastianLevano/NexaERP/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](./LICENSE)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Filament v4](https://img.shields.io/badge/Filament-v4-F59E0B)](https://filamentphp.com)
[![Vue 3](https://img.shields.io/badge/Vue-3-4FC08D?logo=vue.js&logoColor=white)](https://vuejs.org)

ERP interno para PyMEs single-tenant: inventario, ventas y clientes, con UI dark estilo Linear/Stripe.

## Stack

- **Backend**: Laravel 13 · PHP 8.3 · MySQL 8
- **Panel back-office**: Filament v4 (tema dark custom zinc + violet)
- **POS y vistas hero**: Inertia + Vue 3 + TypeScript + Tailwind v4 + shadcn-vue
- **PDF**: barryvdh/laravel-dompdf
- **Permisos**: spatie/laravel-permission
- **Tests**: Pest 4

## Requisitos locales

- PHP 8.3 + extensiones PDO MySQL
- Composer 2.x
- Node 20+ (con npm)
- MySQL 8 corriendo localmente
- Sólo macOS/Linux probados (Windows debería funcionar con WSL2)

## Instalación

```bash
git clone <repo> NexaERP && cd NexaERP

# Dependencias
composer install
npm install

# Configuración
cp .env.example .env
php artisan key:generate

# Crear BD y usuario MySQL
mysql -uroot -p <<SQL
CREATE DATABASE nexaerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nexaerp_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD';
GRANT ALL PRIVILEGES ON nexaerp.* TO 'nexaerp_user'@'localhost';
FLUSH PRIVILEGES;
SQL

# Editar .env con DB_USERNAME=nexaerp_user y DB_PASSWORD=...

# Migrar + sembrar datos demo
php artisan migrate:fresh --seed
```

## Desarrollo

```bash
# Terminal 1: servidor Laravel
php artisan serve --host=127.0.0.1 --port=8080

# Terminal 2: Vite (HMR)
npm run dev
```

Abre **http://127.0.0.1:8080** (no uses `localhost` si tienes Docker corriendo en `:8000`).

### Cuentas demo (password: `password`)

| Email                    | Rol      | Aterriza en        |
| ------------------------ | -------- | ------------------ |
| admin@nexaerp.test       | Admin    | `/dashboard`       |
| vendedor@nexaerp.test    | Vendedor | `/dashboard`       |
| almacen@nexaerp.test     | Almacén  | `/admin/products`  |

## Comandos útiles

```bash
# Tests
./vendor/bin/pest

# Backup manual
php artisan db:backup
# (los backups quedan en storage/app/backups, retención 14 días)

# Reset de datos demo
php artisan migrate:fresh --seed

# Build de assets para producción
npm run build

# Linter PHP
./vendor/bin/pint
```

## Estructura

```
app/
├── Enums/                 # StockMovementType, SaleStatus, PaymentMethod, DocumentType
├── Filament/              # Back-office (panel /admin)
│   ├── Pages/             # StockAdjustment, Settings
│   ├── Resources/         # Category, Product, Customer, StockMovement
│   └── Widgets/           # LowStockWidget
├── Http/Controllers/
│   ├── App/               # Controladores Inertia (Dashboard, Sale, Pos, búsquedas API)
│   └── Auth/              # LoginController
├── Models/                # Eloquent
├── Services/              # Lógica de negocio (StockService, SaleService, InvoicePdfService)
└── Console/Commands/      # BackupDatabaseCommand

resources/js/
├── Components/
│   ├── data/              # EmptyState, KpiCard, SalesChart
│   ├── sales/             # PaymentDialog, CancelSaleDialog, RecordPaymentDialog
│   └── ui/                # Button, Input, Dialog, Toast, Skeleton, Badge, Card...
├── Layouts/               # AppLayout.vue
├── Pages/                 # Vistas Inertia (Dashboard, Sales/*, Auth/Login)
├── composables/           # useSaleCart, useShortcuts
└── lib/                   # cn, formatters
```

## Reglas de negocio

- `stock_movements` es la **fuente de la verdad**. `products.stock` es un cache denormalizado actualizado vía `StockService`.
- Confirmar una venta crea un `stock_movement type=out` por cada `sale_item`, con `reference` polimórfica a la Sale.
- Anular una venta revierte el stock (tipo `in`) con auditoría.
- Toda operación crítica (confirmar, anular, registrar pago, ajustar stock) corre en `DB::transaction` con `lockForUpdate` sobre `products.stock`.
- Números de venta: `V-YYYY-NNNNN` secuencial por año, generado en transacción.

## Roles y permisos

| Recurso / Acción       | Admin | Vendedor | Almacén |
| ---------------------- | :---: | :------: | :-----: |
| Usuarios CRUD          |   ✔   |    ✘     |    ✘    |
| Clientes CRUD          |   ✔   |    ✔     |    ✘    |
| Productos CRUD         |   ✔   |    ✘     |    ✔    |
| Ajuste de stock        |   ✔   |    ✘     |    ✔    |
| Crear / cobrar venta   |   ✔   |    ✔     |    ✘    |
| Anular venta           |   ✔   |    ✘     |    ✘    |
| Ajustes empresa        |   ✔   |    ✘     |    ✘    |

## Deploy

Ver [`DEPLOY.md`](./DEPLOY.md) para el procedimiento detallado.

## Backups

Programar el scheduler de Laravel en producción:

```bash
* * * * * cd /var/www/nexaerp && php artisan schedule:run >> /dev/null 2>&1
```

Esto ejecuta `db:backup` diario a las 02:00. Los archivos quedan en `storage/app/backups/` (gzipped) con retención de 14 días.

## Soporte / contacto

Issues internos del proyecto.
