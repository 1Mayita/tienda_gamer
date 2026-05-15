<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas — AutoZone Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/landing.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="admin-body">

<?php
require_once '../../config/database.php';
require_once '../../includes/funciones.php';
protegerRuta('admin');
$db    = getDB();
$flash = getFlash();

// Actualizar estado de venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $idVenta    = (int)$_POST['id_venta'];
    $nuevoEstado = sanitizar($_POST['estado_venta']);
    $permitidos  = ['Pendiente','Pagado','Entregado'];
    if (in_array($nuevoEstado, $permitidos)) {
        $s = $db->prepare('UPDATE Venta SET estado_venta=? WHERE id_venta=?');
        $s->execute([$nuevoEstado, $idVenta]);
        setFlash('success', 'Estado actualizado correctamente.');
    }
    header('Location: ventas.php');
    exit;
}

$ventas = $db->query('
    SELECT v.*, u.nombre as cliente, u.correo,
           COUNT(dv.id_detalle) as items
    FROM Venta v
    JOIN Usuario u ON v.id_usuario = u.id_usuario
    LEFT JOIN Detalle_Venta dv ON v.id_venta = dv.id_venta
    GROUP BY v.id_venta
    ORDER BY v.fecha DESC
')->fetchAll();

// Cargar todos los detalles de ventas para el modal
$allDetalles = [];
$stmtDet = $db->query('
    SELECT dv.*, p.nombre as prod_nombre, p.marca, p.imagen
    FROM Detalle_Venta dv
    JOIN Producto p ON dv.id_producto = p.id_producto
');
foreach ($stmtDet->fetchAll() as $d) {
    $allDetalles[$d['id_venta']][] = [
        'nombre'   => $d['prod_nombre'],
        'marca'    => $d['marca'],
        'imagen'   => $d['imagen'],
        'cantidad' => (int)$d['cantidad'],
        'subtotal' => (float)$d['subtotal'],
    ];
}
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Gestión de Ventas</h1>
                <p class="admin-breadcrumb">Admin / Ventas</p>
            </div>
        </div>

        <div class="admin-content">
            <div class="admin-card p-0">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Artículos</th>
                                <th>Total</th>
                                <th>Método</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td style="color:#9899aa">#<?= $v['id_venta'] ?></td>
                                <td>
                                    <p class="fw-600 mb-0" style="color:#fff"><?= htmlspecialchars($v['cliente']) ?></p>
                                    <p style="color:#9899aa;font-size:.78rem" class="mb-0"><?= htmlspecialchars($v['correo']) ?></p>
                                </td>
                                <td style="color:#9899aa"><?= $v['items'] ?> ítem(s)</td>
                                <td class="fw-600" style="color:#4ade80">$<?= number_format($v['total'],2) ?></td>
                                <td style="color:#9899aa;font-size:.82rem">
                                    <?= htmlspecialchars($v['metodo_pago'] ?? '—') ?>
                                </td>
                                <td style="color:#9899aa"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                <td>
                                    <span class="badge-estado <?= strtolower($v['estado_venta']) ?>">
                                        <?= $v['estado_venta'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <!-- VER DETALLE -->
                                        <?php
                                            $ventaData = htmlspecialchars(json_encode([
                                                'id'      => $v['id_venta'],
                                                'cliente' => $v['cliente'],
                                                'correo'  => $v['correo'],
                                                'total'   => (float)$v['total'],
                                                'metodo'  => $v['metodo_pago'] ?? '—',
                                                'fecha'   => $v['fecha'],
                                                'estado'  => $v['estado_venta'],
                                                'items'   => $allDetalles[$v['id_venta']] ?? [],
                                            ]), ENT_QUOTES);
                                        ?>
                                        <button type="button" class="btn-table-action btn-view"
                                                onclick="verVenta(<?= $ventaData ?>)"
                                                title="Ver detalle">👁️</button>

                                        <!-- CAMBIAR ESTADO -->
                                        <form method="POST" class="d-flex gap-1 align-items-center">
                                            <input type="hidden" name="id_venta" value="<?= $v['id_venta'] ?>">
                                            <select name="estado_venta" class="auth-input"
                                                    style="padding:5px 8px;font-size:.78rem;width:120px;">
                                                <option value="Pendiente"  <?= $v['estado_venta']==='Pendiente'  ? 'selected':'' ?>>Pendiente</option>
                                                <option value="Pagado"     <?= $v['estado_venta']==='Pagado'     ? 'selected':'' ?>>Pagado</option>
                                                <option value="Entregado"  <?= $v['estado_venta']==='Entregado'  ? 'selected':'' ?>>Entregado</option>
                                            </select>
                                            <button type="submit" name="actualizar_estado"
                                                    class="btn-table-action btn-edit" title="Actualizar">✓</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL DETALLE DE VENTA -->
<div class="modal fade" id="modalDetalleVenta" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <div>
                    <h5 class="modal-title" id="dv_titulo">Detalle de Venta</h5>
                    <p class="mb-0" style="font-size:.78rem;color:#9899aa" id="dv_fecha"></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem">

                <!-- Info cliente + meta -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:1rem">
                            <p style="font-size:.68rem;letter-spacing:1.5px;color:#666;text-transform:uppercase;margin-bottom:.4rem">Cliente</p>
                            <p class="fw-600 mb-0" style="color:#fff" id="dv_cliente"></p>
                            <p style="color:#9899aa;font-size:.8rem;margin:0" id="dv_correo"></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:1rem">
                            <p style="font-size:.68rem;letter-spacing:1.5px;color:#666;text-transform:uppercase;margin-bottom:.4rem">Método</p>
                            <p class="fw-600 mb-0" style="color:#ccc" id="dv_metodo"></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:1rem">
                            <p style="font-size:.68rem;letter-spacing:1.5px;color:#666;text-transform:uppercase;margin-bottom:.4rem">Estado</p>
                            <span id="dv_estado"></span>
                        </div>
                    </div>
                </div>

                <!-- Tabla de productos -->
                <p style="font-size:.68rem;letter-spacing:1.5px;color:#666;text-transform:uppercase;margin-bottom:.75rem">Productos comprados</p>
                <div class="table-responsive mb-4">
                    <table class="admin-table" id="dv_tabla">
                        <thead>
                            <tr>
                                <th>Vehículo</th>
                                <th style="text-align:center">Cant.</th>
                                <th style="text-align:right">Precio Unit.</th>
                                <th style="text-align:right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="dv_items"></tbody>
                    </table>
                </div>

                <!-- Totales -->
                <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:1rem;max-width:300px;margin-left:auto">
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:#9899aa;font-size:.85rem">Subtotal</span>
                        <span style="color:#fff;font-weight:600" id="dv_subtotal"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:#9899aa;font-size:.85rem">IVA (13%)</span>
                        <span style="color:#fff;font-weight:600" id="dv_iva"></span>
                    </div>
                    <div class="d-flex justify-content-between" style="border-top:1px solid rgba(255,255,255,.08);padding-top:.75rem;margin-top:.25rem">
                        <span style="color:#fff;font-weight:700">TOTAL</span>
                        <span style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:#e8272b" id="dv_total"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-dark">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="dv_link_factura" href="#" target="_blank" class="btn btn-accent">🧾 Ver Factura Completa</a>
            </div>
        </div>
    </div>
</div>

<style>
.btn-view {
    background:rgba(99,179,237,.12); border:1px solid rgba(99,179,237,.35); color:#63b3ed;
}
.btn-view:hover { background:rgba(99,179,237,.25); }
</style>

<script>
const IMG_URL_VENTAS = <?= json_encode(IMG_PRODUCTOS_URL) ?>;
const IMG_DEF_VENTAS = <?= json_encode(IMG_DEFAULT_URL) ?>;
const BASE = <?= json_encode(BASE_URL) ?>;

function verVenta(v) {
    document.getElementById('dv_titulo').textContent  = '📦 Pedido #' + v.id;
    document.getElementById('dv_fecha').textContent   = '📅 ' + new Date(v.fecha).toLocaleString('es');
    document.getElementById('dv_cliente').textContent = v.cliente;
    document.getElementById('dv_correo').textContent  = v.correo;
    document.getElementById('dv_metodo').textContent  = v.metodo || '—';

    // Estado badge
    var ecls = {Pendiente:'pendiente', Pagado:'pagado', Entregado:'entregado'};
    document.getElementById('dv_estado').innerHTML =
        '<span class="badge-estado ' + (ecls[v.estado]||'pendiente') + '">' + v.estado + '</span>';

    // Items
    var tbody = document.getElementById('dv_items');
    tbody.innerHTML = '';
    (v.items || []).forEach(function(it) {
        var pu = it.cantidad > 0 ? (it.subtotal / it.cantidad) : 0;
        var fmt = function(n) { return '$' + parseFloat(n).toLocaleString('en-US',{minimumFractionDigits:2}); };
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><div class="d-flex align-items-center gap-2">' +
            '<img src="' + IMG_URL_VENTAS + it.imagen + '" onerror="this.onerror=null;this.src=\'' + IMG_DEF_VENTAS + '\'" style="width:36px;height:36px;border-radius:6px;object-fit:cover;border:1px solid rgba(255,255,255,.08)">' +
            '<div><p class="mb-0 fw-600" style="color:#fff;font-size:.85rem">' + it.nombre + '</p>' +
            '<p class="mb-0" style="color:#9899aa;font-size:.75rem">' + it.marca + '</p></div></div></td>' +
            '<td style="text-align:center;color:#ccc">' + it.cantidad + '</td>' +
            '<td style="text-align:right;color:#ccc">' + fmt(pu) + '</td>' +
            '<td style="text-align:right;font-weight:700;color:#fff">' + fmt(it.subtotal) + '</td>';
        tbody.appendChild(tr);
    });

    var fmt = function(n) { return '$' + parseFloat(n).toLocaleString('en-US',{minimumFractionDigits:2}); };
    var iva = v.total * 0.13;
    document.getElementById('dv_subtotal').textContent = fmt(v.total);
    document.getElementById('dv_iva').textContent      = fmt(iva);
    document.getElementById('dv_total').textContent    = fmt(v.total + iva);
    document.getElementById('dv_link_factura').href    = BASE + 'views/client/factura.php?id=' + v.id;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleVenta')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/toasts.js"></script>
<?php include '../../includes/flash_toast.php'; ?>
<script src="../../assets/js/main.js"></script>
</body>
</html>
