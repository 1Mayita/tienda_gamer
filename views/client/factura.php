<?php
require_once '../../config/database.php';
require_once '../../includes/funciones.php';
protegerRuta();
$db = getDB();

$id_venta = (int)($_GET['id'] ?? 0);
if ($id_venta <= 0) {
    header('Location: ' . BASE_URL . 'views/client/dashboard.php'); exit;
}

$stmt = $db->prepare('
    SELECT v.*, u.nombre as cliente_nombre, u.correo as cliente_correo
    FROM Venta v
    JOIN Usuario u ON v.id_usuario = u.id_usuario
    WHERE v.id_venta = ?
');
$stmt->execute([$id_venta]);
$venta = $stmt->fetch();

if (!$venta || ($_SESSION['rol'] !== 'admin' && (int)$venta['id_usuario'] !== (int)$_SESSION['id_usuario'])) {
    header('Location: ' . BASE_URL . 'views/client/dashboard.php'); exit;
}

$stmtDet = $db->prepare('
    SELECT dv.*, p.nombre as prod_nombre, p.marca, p.imagen
    FROM Detalle_Venta dv
    JOIN Producto p ON dv.id_producto = p.id_producto
    WHERE dv.id_venta = ?
');
$stmtDet->execute([$id_venta]);
$detalles = $stmtDet->fetchAll();

$subtotal  = (float)$venta['total'];
$iva       = $subtotal * 0.13;
$total_final = $subtotal + $iva;

$num_factura = 'FAC-' . str_pad($id_venta, 6, '0', STR_PAD_LEFT);
$metodos_icon = ['Efectivo'=>'💵','QR'=>'📱','Tarjeta'=>'💳','Transferencia'=>'🏦'];
$metodo_icon  = $metodos_icon[$venta['metodo_pago'] ?? ''] ?? '💳';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?= $num_factura ?> — AutoZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/landing.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .factura-wrap {
            max-width: 800px; margin: 0 auto; background: #0f0f18;
            border: 1px solid rgba(255,255,255,.08); border-radius: 16px;
            overflow: hidden;
        }
        .factura-header {
            background: linear-gradient(135deg,#1a0a0a 0%,#0f0f18 100%);
            padding: 2rem 2.5rem; display: flex;
            justify-content: space-between; align-items: flex-start;
            border-bottom: 2px solid rgba(232,39,43,.3);
        }
        .factura-logo {
            font-family: 'Bebas Neue', sans-serif; font-size: 2rem; color: #fff; line-height: 1;
        }
        .factura-logo span { color: #e8272b; }
        .factura-logo small { display: block; font-size: .7rem; letter-spacing: 3px; color: #666; font-family: 'Inter',sans-serif; }
        .factura-num {
            text-align: right;
        }
        .factura-num .fn-label { font-size: .72rem; letter-spacing: 2px; color: #666; text-transform: uppercase; }
        .factura-num .fn-value { font-family: 'Bebas Neue',sans-serif; font-size: 1.8rem; color: #e8272b; }
        .factura-num .fn-date  { font-size: .82rem; color: #888; margin-top: .2rem; }
        .factura-body { padding: 2rem 2.5rem; }
        .factura-section { margin-bottom: 1.75rem; }
        .factura-section-title {
            font-size: .68rem; font-weight: 700; letter-spacing: 2px; color: #666;
            text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,.06);
            padding-bottom: .5rem; margin-bottom: 1rem;
        }
        .cliente-info p { margin: 0; color: #ccc; font-size: .9rem; }
        .cliente-info .cn { font-weight: 700; color: #fff; font-size: 1.05rem; }
        .factura-table { width: 100%; border-collapse: collapse; }
        .factura-table th {
            font-size: .68rem; font-weight: 700; letter-spacing: 1.5px;
            color: #666; text-transform: uppercase; padding: .6rem .75rem;
            border-bottom: 1px solid rgba(255,255,255,.06); text-align: left;
        }
        .factura-table td { padding: .9rem .75rem; border-bottom: 1px solid rgba(255,255,255,.04); }
        .factura-table tr:last-child td { border-bottom: none; }
        .prod-cell { display: flex; align-items: center; gap: .75rem; }
        .prod-thumb-f { width: 42px; height: 42px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(255,255,255,.08); }
        .prod-name-f { font-weight: 600; color: #fff; font-size: .88rem; }
        .prod-brand-f { font-size: .75rem; color: #888; }
        .factura-totales { border-top: 1px solid rgba(255,255,255,.08); padding-top: 1.25rem; }
        .tot-row { display: flex; justify-content: space-between; align-items: center; padding: .4rem 0; }
        .tot-label { color: #999; font-size: .88rem; }
        .tot-val   { color: #fff; font-weight: 600; font-size: .88rem; }
        .tot-total-row { border-top: 1px solid rgba(232,39,43,.3); margin-top: .5rem; padding-top: .75rem; }
        .tot-total-row .tot-label { color: #fff; font-weight: 700; font-size: 1rem; }
        .tot-total-row .tot-val   { font-family: 'Bebas Neue',sans-serif; font-size: 1.6rem; color: #e8272b; }
        .factura-footer {
            background: rgba(255,255,255,.02); border-top: 1px solid rgba(255,255,255,.06);
            padding: 1.25rem 2.5rem; display: flex;
            justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;
        }
        .metodo-badge {
            display: inline-flex; align-items: center; gap: .5rem;
            background: rgba(232,39,43,.1); border: 1px solid rgba(232,39,43,.25);
            color: #e8272b; padding: .4rem .9rem; border-radius: 20px; font-size: .82rem; font-weight: 600;
        }
        .estado-badge {
            padding: .35rem .8rem; border-radius: 20px; font-size: .78rem; font-weight: 700; text-transform: uppercase;
        }
        .estado-pendiente { background: rgba(245,158,11,.15); color: #f59e0b; }
        .estado-pagado    { background: rgba(34,197,94,.15);  color: #4ade80; }
        .estado-entregado { background: rgba(59,130,246,.15); color: #60a5fa; }

        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .factura-wrap { border: 1px solid #ccc; border-radius: 0; background: #fff; }
            .factura-header { background: #f8f8f8 !important; border-bottom: 2px solid #e8272b; }
            .factura-logo, .factura-logo small, .fn-value, .fn-date, .fn-label { color: #333 !important; }
            .factura-section-title { color: #666 !important; }
            .prod-name-f, .tot-val, .tot-total-row .tot-label { color: #111 !important; }
            .prod-brand-f, .tot-label, .cliente-info p { color: #555 !important; }
        }
    </style>
</head>
<body class="admin-body">

<!-- NAVBAR -->
<nav class="client-nav no-print">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="../../index.php" class="brand-logo" style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:#fff;text-decoration:none;">
            <span style="color:#e8272b">⬡</span> AUTO<span style="color:#e8272b">ZONE</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">📦 Mis Pedidos</a>
            <button onclick="window.print()" class="btn btn-accent btn-sm">🖨️ Imprimir</button>
        </div>
    </div>
</nav>

<div class="client-main">
    <div class="container py-5">

        <!-- Botones superiores -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h1 class="section-title mb-1" style="font-size:1.6rem">Factura de Compra</h1>
                <p style="color:#888;margin:0">Tu compra se ha procesado correctamente.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="catalogo.php" class="btn btn-outline-secondary">🛒 Seguir comprando</a>
                <button onclick="window.print()" class="btn btn-accent">🖨️ Imprimir / Guardar PDF</button>
            </div>
        </div>

        <!-- FACTURA -->
        <div class="factura-wrap">

            <!-- HEADER -->
            <div class="factura-header">
                <div class="factura-logo">
                    <span>⬡</span> AUTO<span>ZONE</span>
                    <small>Motor & Lifestyle</small>
                </div>
                <div class="factura-num">
                    <div class="fn-label">Factura N°</div>
                    <div class="fn-value"><?= $num_factura ?></div>
                    <div class="fn-date">📅 <?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></div>
                </div>
            </div>

            <div class="factura-body">

                <!-- CLIENTE -->
                <div class="factura-section">
                    <div class="factura-section-title">Datos del Cliente</div>
                    <div class="cliente-info">
                        <p class="cn"><?= htmlspecialchars($venta['cliente_nombre']) ?></p>
                        <p>✉️ <?= htmlspecialchars($venta['cliente_correo']) ?></p>
                    </div>
                </div>

                <!-- PRODUCTOS -->
                <div class="factura-section">
                    <div class="factura-section-title">Detalle de Productos</div>
                    <table class="factura-table">
                        <thead>
                            <tr>
                                <th style="width:45%">Vehículo</th>
                                <th style="text-align:center">Cant.</th>
                                <th style="text-align:right">Precio Unit.</th>
                                <th style="text-align:right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $d):
                                $precio_unit = $d['cantidad'] > 0 ? ($d['subtotal'] / $d['cantidad']) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="prod-cell">
                                        <img src="<?= IMG_PRODUCTOS_URL . htmlspecialchars($d['imagen']) ?>"
                                             onerror="this.onerror=null;this.src='<?= IMG_DEFAULT_URL ?>'"
                                             class="prod-thumb-f" alt="">
                                        <div>
                                            <div class="prod-name-f"><?= htmlspecialchars($d['prod_nombre']) ?></div>
                                            <div class="prod-brand-f"><?= htmlspecialchars($d['marca']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align:center;color:#ccc"><?= $d['cantidad'] ?></td>
                                <td style="text-align:right;color:#ccc">$<?= number_format($precio_unit, 2) ?></td>
                                <td style="text-align:right;font-weight:700;color:#fff">$<?= number_format($d['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- TOTALES -->
                <div class="factura-totales">
                    <div class="tot-row">
                        <span class="tot-label">Subtotal</span>
                        <span class="tot-val">$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="tot-row">
                        <span class="tot-label">IVA (13%)</span>
                        <span class="tot-val">$<?= number_format($iva, 2) ?></span>
                    </div>
                    <div class="tot-row tot-total-row">
                        <span class="tot-label">TOTAL A PAGAR</span>
                        <span class="tot-val">$<?= number_format($total_final, 2) ?></span>
                    </div>
                </div>

            </div>

            <!-- FOOTER -->
            <div class="factura-footer">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="metodo-badge"><?= $metodo_icon ?> <?= htmlspecialchars($venta['metodo_pago'] ?? 'N/A') ?></span>
                    <?php
                        $estadoClass = match($venta['estado_venta']) {
                            'Pagado'    => 'estado-pagado',
                            'Entregado' => 'estado-entregado',
                            default     => 'estado-pendiente',
                        };
                    ?>
                    <span class="estado-badge <?= $estadoClass ?>">
                        <?= $venta['estado_venta'] ?>
                    </span>
                </div>
                <div style="text-align:right">
                    <p style="font-size:.75rem;color:#555;margin:0">Pedido #<?= $id_venta ?></p>
                    <p style="font-size:.75rem;color:#555;margin:0">AutoZone © <?= date('Y') ?> — Gracias por tu compra</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/toasts.js"></script>
<?php include '../../includes/flash_toast.php'; ?>
</body>
</html>
