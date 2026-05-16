<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>NexaERP · Manual de Usuario</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0 0 4px 0; letter-spacing: -0.3px; }
        h2 { font-size: 14px; margin: 22px 0 6px 0; color: #2a2a2a; }
        h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6a6a6a; margin: 16px 0 4px 0; }
        p { margin: 0 0 8px 0; }
        ol, ul { margin: 0 0 8px 18px; padding: 0; }
        li { margin-bottom: 3px; }
        .muted { color: #6a6a6a; font-size: 9px; }
        .role { padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 600; }
        .role-admin { background: #ede9fe; color: #5b21b6; }
        .role-vendedor { background: #d1fae5; color: #065f46; }
        .role-almacen { background: #fef3c7; color: #92400e; }
        .kbd {
            display: inline-block;
            padding: 1px 5px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9px;
            background: #f4f4f5;
            border: 1px solid #d4d4d8;
            border-radius: 3px;
        }
        .page-break { page-break-before: always; }
        hr { border: none; border-top: 1px solid #e4e4e7; margin: 14px 0; }
        .cover-block {
            margin-top: 60mm;
            border-left: 4px solid #8b5cf6;
            padding-left: 14px;
        }
        .card {
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 10px 12px;
            margin: 8px 0;
            background: #fafafa;
        }
    </style>
</head>
<body>

<!-- ========= PORTADA ========= -->
<div class="cover-block">
    <h1>NexaERP</h1>
    <p style="font-size: 13px; color: #6a6a6a; margin: 0 0 4px 0;">
        Manual de usuario por rol
    </p>
    <p class="muted">
        Versión MVP · {{ now()->format('F Y') }}
    </p>
</div>

<div style="position: absolute; bottom: 0; font-size: 9px; color: #9a9a9a;">
    Este documento explica el uso del sistema NexaERP a los tres perfiles
    operativos: Administrador, Vendedor y personal de Almacén. Léelo en el
    rol que te aplica; las demás secciones son referencia opcional.
</div>

<!-- ========= ACCESO COMÚN ========= -->
<div class="page-break"></div>

<h2>Antes de empezar</h2>

<h3>Cómo ingresar al sistema</h3>
<ol>
    <li>Abre la dirección que te dio el administrador (ej: <span class="kbd">https://erp.tuempresa.com</span>).</li>
    <li>Ingresa tu correo electrónico y contraseña.</li>
    <li>Si tu cuenta está activa, serás redirigido automáticamente a tu pantalla principal.</li>
</ol>

<h3>Cómo cerrar sesión</h3>
<p>
    En la esquina inferior izquierda del menú lateral verás tu nombre y un ícono
    de salida. Click en él te cierra sesión.
</p>

<h3>Atajos de teclado generales</h3>
<ul>
    <li><span class="kbd">⌘</span> + <span class="kbd">K</span> (o <span class="kbd">Ctrl</span> + <span class="kbd">K</span> en Windows) — enfoca el buscador en el POS</li>
    <li><span class="kbd">Esc</span> — cierra cualquier ventana modal abierta</li>
    <li><span class="kbd">Tab</span> — navega entre campos del formulario</li>
</ul>

<!-- ========= ADMIN ========= -->
<div class="page-break"></div>

<p><span class="role role-admin">ADMINISTRADOR</span></p>
<h1>Manual del Administrador</h1>
<p class="muted">Acceso total al sistema. Configura, monitorea y resuelve incidencias.</p>

<h2>1. Configurar la empresa</h2>
<p>
    Lo primero después de instalar el sistema. Ve a
    <strong>/admin/settings</strong> y completa razón social, RUC, dirección,
    teléfono y correo. También define el porcentaje de IGV (usualmente 0.18
    en Perú) y la moneda. Estos datos aparecerán en cada comprobante PDF.
</p>

<h2>2. Crear usuarios</h2>
<p>
    Los usuarios se crean desde la consola con artisan (en esta versión MVP).
    Pide al desarrollador que ejecute el seeder con los emails de tu equipo
    o usa <strong>tinker</strong> para crear usuarios y asignarles uno de
    los tres roles: Admin, Vendedor o Almacén.
</p>

<h2>3. Catálogo de productos</h2>
<p>Desde el panel <strong>/admin</strong>:</p>
<ol>
    <li><strong>Categorías</strong>: agrupa productos (ej: Bebidas, Snacks, Limpieza).</li>
    <li><strong>Productos</strong>: alta con SKU autogenerado, nombre, costo, precio de venta y stock mínimo. El stock inicial se carga con un "Ajuste de Stock".</li>
    <li>Activos/inactivos: desactivar oculta el producto del POS sin borrar historial.</li>
</ol>

<h2>4. Anular ventas</h2>
<p>
    Solo el administrador puede anular una venta. Ve a <strong>/sales</strong>,
    abre el comprobante y haz click en <strong>Anular</strong>. Se te pedirá
    un motivo opcional. La anulación:
</p>
<ul>
    <li>Cambia el estado de la venta a "Anulada".</li>
    <li>Devuelve automáticamente el stock al inventario (con un movimiento auditado).</li>
    <li>No se puede deshacer; si fue un error, registra una venta nueva.</li>
</ul>

<h2>5. Backups</h2>
<div class="card">
    El sistema genera un backup automático todos los días a las 02:00.
    Se guardan en <span class="kbd">storage/app/backups/</span> por 14 días.
    Si necesitas un backup manual antes de un cambio importante, pide al
    técnico ejecutar <span class="kbd">php artisan db:backup</span>.
</div>

<h2>6. Reportes</h2>
<ul>
    <li><strong>Dashboard</strong> (/dashboard): KPIs del día y del mes, gráfico de 30 días, top productos y últimas ventas.</li>
    <li><strong>Exportar CSV</strong>: botón en /sales que respeta los filtros activos. Ábrelo con Excel o Google Sheets.</li>
    <li><strong>Movimientos de stock</strong>: /admin/stock-movements con su propio export CSV.</li>
</ul>

<!-- ========= VENDEDOR ========= -->
<div class="page-break"></div>

<p><span class="role role-vendedor">VENDEDOR</span></p>
<h1>Manual del Vendedor</h1>
<p class="muted">Atiende clientes, registra ventas y cobra. No tiene acceso al inventario.</p>

<h2>1. Registrar una venta (Punto de Venta)</h2>
<p>
    El POS es tu pantalla principal. Ve a <strong>/sales/pos</strong> o
    click en "Punto de venta" en el menú lateral.
</p>

<h3>Flujo típico</h3>
<ol>
    <li>Escribe en el buscador (arriba a la izquierda) el nombre o SKU del producto. Usa <span class="kbd">⌘</span>+<span class="kbd">K</span> para enfocarlo rápido.</li>
    <li>Click en una tarjeta de producto para agregarlo al carrito (a la derecha).</li>
    <li>Ajusta cantidades con los botones <span class="kbd">−</span> y <span class="kbd">+</span> en cada línea.</li>
    <li>Si el cliente quiere su comprobante con datos, click en "Agregar cliente" arriba del carrito. Busca por nombre o documento. Si no existe, créalo desde el formulario inline.</li>
    <li>Cuando el carrito tenga todo lo que va a llevar, click en <strong>"Cobrar"</strong>.</li>
    <li>En el modal de pago: elige método (Efectivo, Transferencia, Tarjeta), ingresa el monto recibido. El vuelto se calcula solo.</li>
    <li>Click en <strong>"Confirmar venta"</strong>. Verás el comprobante con número V-2026-NNNNN listo para imprimir.</li>
</ol>

<h2>2. Cobros parciales</h2>
<p>
    Si el cliente paga menos del total, la venta queda en estado "Confirmada"
    con un saldo pendiente. Más tarde puedes completar el cobro:
</p>
<ol>
    <li>Ve a <strong>/sales</strong> y abre la venta con saldo.</li>
    <li>Click en <strong>"Registrar pago"</strong>.</li>
    <li>Ingresa monto y método. Cuando el saldo llegue a 0, el estado pasa a "Pagada".</li>
</ol>

<h2>3. Descargar/imprimir el comprobante</h2>
<p>
    En el detalle de cualquier venta, click en <strong>"PDF"</strong> arriba
    a la derecha. Se abre en una pestaña nueva con el comprobante formateado
    A5 listo para imprimir.
</p>

<h2>4. Clientes</h2>
<p>
    Desde <strong>/admin/customers</strong> (sí, Vendedor también puede entrar
    al panel solo para clientes). Crea, edita y busca clientes. El sistema
    valida que no se duplique un número de documento.
</p>

<h2>5. Qué hacer si...</h2>
<div class="card">
    <strong>El producto no aparece en el buscador del POS:</strong> verifica
    que esté activo. Si no lo encuentras, pide a Almacén que lo dé de alta.
</div>
<div class="card">
    <strong>El stock del producto es 0 o muy bajo:</strong> la tarjeta saldrá
    en rojo o gris y no podrás agregarlo. Avisa a Almacén para reponer.
</div>
<div class="card">
    <strong>El cliente quiere anular una compra ya cobrada:</strong> tú no
    tienes permiso de anular. Pasa la venta al Administrador.
</div>

<!-- ========= ALMACÉN ========= -->
<div class="page-break"></div>

<p><span class="role role-almacen">ALMACÉN</span></p>
<h1>Manual del Almacén</h1>
<p class="muted">Mantiene productos, controla el inventario y reabastece.</p>

<h2>1. Pantalla principal</h2>
<p>
    Cuando inicias sesión aterrizas directamente en el panel
    <strong>/admin</strong>. El widget "Productos bajo mínimo" te muestra
    en tiempo real qué productos necesitan reposición.
</p>

<h2>2. Dar de alta un producto nuevo</h2>
<ol>
    <li>Click en <strong>"Catálogo → Productos"</strong> (menú lateral).</li>
    <li>Botón violeta <strong>"Nuevo producto"</strong> arriba a la derecha.</li>
    <li>Completa: SKU (autogenerado, puedes editarlo), nombre, categoría, costo, precio de venta, stock mínimo.</li>
    <li>El <strong>stock actual</strong> queda en 0; lo cargas con un Ajuste de Stock (siguiente sección).</li>
</ol>

<h2>3. Cargar / corregir stock</h2>
<p>
    Toda modificación de inventario pasa por <strong>"Inventario → Ajuste de Stock"</strong>.
</p>
<ol>
    <li>Selecciona el producto del desplegable (ves el stock actual junto al nombre).</li>
    <li>Cantidad: <strong>positiva</strong> para sumar (entrada/compra),
        <strong>negativa</strong> para restar (merma, pérdida).</li>
    <li>Motivo: obligatorio. Sé específico ("Compra 20 unidades a proveedor",
        "Merma por vencimiento", "Inventario físico").</li>
    <li>Click en <strong>"Registrar ajuste"</strong>. Verás una notificación
        con el nuevo stock total.</li>
</ol>

<h2>4. Consultar movimientos</h2>
<p>
    Ve a <strong>"Inventario → Movimientos de stock"</strong> para ver el
    historial completo, auditado. Cada movimiento muestra fecha, producto,
    tipo (entrada / salida / ajuste), cantidad, usuario que lo registró y
    notas. Puedes filtrar por producto, tipo o rango de fechas, y exportar
    a CSV.
</p>

<h2>5. Categorías</h2>
<p>
    Desde <strong>"Catálogo → Categorías"</strong> agregas o renombras
    categorías. El slug se genera automáticamente.
</p>

<h2>6. Lo que NO puedes hacer (por diseño)</h2>
<ul>
    <li>Editar el stock directamente en el formulario de Productos. Está
        deshabilitado a propósito: todo cambio debe pasar por "Ajuste de
        Stock" para mantener auditoría.</li>
    <li>Ver clientes ni ventas: ese módulo es exclusivo del equipo comercial.</li>
    <li>Configurar empresa, IGV o usuarios: pertenece al Administrador.</li>
</ul>

<h2>7. Buenas prácticas</h2>
<div class="card">
    <strong>Conteo físico mensual:</strong> recorre el almacén físicamente y
    contrasta con el sistema. Si hay diferencias, registra un ajuste con
    motivo "Inventario físico" indicando la fecha.
</div>
<div class="card">
    <strong>Stock mínimo:</strong> revísalo cada trimestre. Productos que se
    venden rápido pueden necesitar un mínimo más alto.
</div>

</body>
</html>
