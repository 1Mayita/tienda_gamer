<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta — AutoZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/landing.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="admin-body">

<?php
require_once '../../config/database.php';
require_once '../../includes/funciones.php';
protegerRuta();
$db    = getDB();
$flash = getFlash();

$id = (int)$_SESSION['id_usuario'];

$totalPedidos  = $db->prepare('SELECT COUNT(*) FROM Venta WHERE id_usuario=?'); $totalPedidos->execute([$id]);
$totalPedidos  = $totalPedidos->fetchColumn();
$totalGastado  = $db->prepare('SELECT COALESCE(SUM(total),0) FROM Venta WHERE id_usuario=?'); $totalGastado->execute([$id]);
$totalGastado  = $totalGastado->fetchColumn();
$totalFavoritos= $db->prepare('SELECT COUNT(*) FROM Favorito WHERE id_usuario=?'); $totalFavoritos->execute([$id]);
$totalFavoritos= $totalFavoritos->fetchColumn();

$pedidos = $db->prepare('
    SELECT v.*, COUNT(dv.id_detalle) as num_items
    FROM Venta v
    LEFT JOIN Detalle_Venta dv ON v.id_venta=dv.id_venta
    WHERE v.id_usuario=?
    GROUP BY v.id_venta
    ORDER BY v.fecha DESC
');
$pedidos->execute([$id]);
$pedidos = $pedidos->fetchAll();

// Cargar items de cada pedido para el modal
$pedidosDetalle = [];
if (!empty($pedidos)) {
    $ids = array_column($pedidos, 'id_venta');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtDet = $db->prepare("
        SELECT dv.*, p.nombre as prod_nombre, p.marca, p.imagen
        FROM Detalle_Venta dv
        JOIN Producto p ON dv.id_producto = p.id_producto
        WHERE dv.id_venta IN ($placeholders)
    ");
    $stmtDet->execute($ids);
    foreach ($stmtDet->fetchAll() as $d) {
        $pedidosDetalle[$d['id_venta']][] = [
            'nombre'   => $d['prod_nombre'],
            'marca'    => $d['marca'],
            'imagen'   => $d['imagen'],
            'cantidad' => (int)$d['cantidad'],
            'subtotal' => (float)$d['subtotal'],
        ];
    }
}
?>

<nav class="client-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="../../index.php" class="brand-logo" style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:#fff;text-decoration:none;">
            <span style="color:#e8272b">⬡</span> AUTO<span style="color:#e8272b">ZONE</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="catalogo.php"  class="nav-link text-white-50">Catálogo</a>
            <a href="favoritos.php" class="nav-link text-white-50">♡ Favoritos</a>
            <a href="carrito.php"   class="nav-link text-white">🛒 (<?= totalCarrito() ?>)</a>
            <a href="dashboard.php" class="nav-link" style="color:#e8272b;font-weight:600">📦 Mis Pedidos</a>
            <a href="../../controllers/AuthController.php?accion=logout" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="client-main">
    <div class="container py-5">
        <div class="d-flex align-items-center gap-4 mb-5">
            <div class="sidebar-avatar" style="width:56px;height:56px;font-size:1.4rem;">
                <?= strtoupper(substr($_SESSION['nombre'],0,1)) ?>
            </div>
            <div>
                <h1 class="section-title mb-0">Hola, <?= htmlspecialchars(explode(' ',$_SESSION['nombre'])[0]) ?> 👋</h1>
                <p style="color:#aaa;margin:0"><?= htmlspecialchars($_SESSION['correo']) ?></p>
            </div>
        </div>

        <!-- STATS -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card stat-red">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-body">
                        <span class="stat-value"><?= $totalPedidos ?></span>
                        <span class="stat-label">Pedidos Realizados</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-gold">
                    <div class="stat-icon">💰</div>
                    <div class="stat-body">
                        <span class="stat-value">$<?= number_format($totalGastado,0,'.',',') ?></span>
                        <span class="stat-label">Total Invertido</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">♥</div>
                    <div class="stat-body">
                        <span class="stat-value"><?= $totalFavoritos ?></span>
                        <span class="stat-label">Favoritos Guardados</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- HISTORIAL -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">📦 Historial de Pedidos</h3>
                <a href="catalogo.php" class="admin-card-link">Seguir comprando →</a>
            </div>
            <?php if (empty($pedidos)): ?>
            <p style="color:#aaa" class="text-center py-4">Aún no has realizado ningún pedido.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Artículos</th>
                            <th>Método</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $p):
                            $pData = htmlspecialchars(json_encode([
                                'id'     => $p['id_venta'],
                                'fecha'  => $p['fecha'],
                                'metodo' => $p['metodo_pago'] ?? '—',
                                'estado' => $p['estado_venta'],
                                'total'  => (float)$p['total'],
                                'items'  => $pedidosDetalle[$p['id_venta']] ?? [],
                            ]), ENT_QUOTES);
                        ?>
                        <tr>
                            <td class="fw-600 text-white">#<?= $p['id_venta'] ?></td>
                            <td style="color:#aaa"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                            <td style="color:#ccc"><?= $p['num_items'] ?> ítem(s)</td>
                            <td style="color:#ccc"><?= htmlspecialchars($p['metodo_pago'] ?? '—') ?></td>
                            <td class="fw-600 text-white">$<?= number_format($p['total'],2) ?></td>
                            <td>
                                <span class="badge-estado <?= strtolower($p['estado_venta']) ?>">
                                    <?= $p['estado_venta'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn-table-action btn-view"
                                            onclick="verPedido(<?= $pData ?>)"
                                            title="Ver detalle">👁️</button>
                                    <a href="factura.php?id=<?= $p['id_venta'] ?>"
                                       class="btn-table-action btn-edit"
                                       title="Ver factura">🧾</a>
                                    <?php if ($p['estado_venta'] === 'Pendiente'): ?>
                                    <form method="POST" action="../../controllers/ProductoController.php"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Cancelar el pedido #<?= $p['id_venta'] ?>? Se restaurará el stock y no podrás deshacerlo.')">
                                        <input type="hidden" name="accion" value="cancelar_pedido">
                                        <input type="hidden" name="id_venta" value="<?= $p['id_venta'] ?>">
                                        <button type="submit" class="btn-table-action btn-delete"
                                                title="Cancelar pedido">🗑️</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL DETALLE PEDIDO -->
<div class="modal fade" id="modalPedido" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <div>
                    <h5 class="modal-title" id="mp_titulo">Detalle del Pedido</h5>
                    <p class="mb-0" style="font-size:.78rem;color:#9899aa" id="mp_fecha"></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem">

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:.9rem">
                            <p style="font-size:.67rem;letter-spacing:1.5px;color:#666;text-transform:uppercase;margin-bottom:.3rem">Método de Pago</p>
                            <p class="fw-600 mb-0" style="color:#ccc" id="mp_metodo"></p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:.9rem">
                            <p style="font-size:.67rem;letter-spacing:1.5px;color:#666;text-transform:uppercase;margin-bottom:.3rem">Estado</p>
                            <span id="mp_estado"></span>
                        </div>
                    </div>
                </div>

                <p style="font-size:.68rem;letter-spacing:1.5px;color:#666;text-transform:uppercase;margin-bottom:.75rem">Productos</p>
                <div class="table-responsive mb-4">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Vehículo</th>
                                <th style="text-align:center">Cant.</th>
                                <th style="text-align:right">Precio Unit.</th>
                                <th style="text-align:right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="mp_items"></tbody>
                    </table>
                </div>

                <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:1rem;max-width:280px;margin-left:auto">
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:#9899aa;font-size:.85rem">Subtotal</span>
                        <span style="color:#fff;font-weight:600" id="mp_sub"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:#9899aa;font-size:.85rem">IVA (13%)</span>
                        <span style="color:#fff;font-weight:600" id="mp_iva"></span>
                    </div>
                    <div class="d-flex justify-content-between" style="border-top:1px solid rgba(255,255,255,.08);padding-top:.75rem;margin-top:.25rem">
                        <span style="color:#fff;font-weight:700">TOTAL</span>
                        <span style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:#e8272b" id="mp_total"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-dark">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="mp_link_factura" href="#" class="btn btn-accent">🧾 Ver Factura Completa</a>
            </div>
        </div>
    </div>
</div>

<style>
.btn-view { background:rgba(99,179,237,.12);border:1px solid rgba(99,179,237,.35);color:#63b3ed; }
.btn-view:hover { background:rgba(99,179,237,.25); }
</style>

<script>
const IMG_URL_D = <?= json_encode(IMG_PRODUCTOS_URL) ?>;
const IMG_DEF_D = <?= json_encode(IMG_DEFAULT_URL) ?>;

function verPedido(p) {
    document.getElementById('mp_titulo').textContent = '📦 Pedido #' + p.id;
    document.getElementById('mp_fecha').textContent  = '📅 ' + new Date(p.fecha).toLocaleString('es');
    document.getElementById('mp_metodo').textContent = p.metodo || '—';
    document.getElementById('mp_link_factura').href  = 'factura.php?id=' + p.id;

    var ecls = {Pendiente:'pendiente', Pagado:'pagado', Entregado:'entregado'};
    document.getElementById('mp_estado').innerHTML =
        '<span class="badge-estado ' + (ecls[p.estado]||'pendiente') + '">' + p.estado + '</span>';

    var tbody = document.getElementById('mp_items');
    tbody.innerHTML = '';
    (p.items || []).forEach(function(it) {
        var pu  = it.cantidad > 0 ? (it.subtotal / it.cantidad) : 0;
        var fmt = function(n) { return '$' + parseFloat(n).toLocaleString('en-US',{minimumFractionDigits:2}); };
        var tr  = document.createElement('tr');
        tr.innerHTML =
            '<td><div class="d-flex align-items-center gap-2">' +
            '<img src="' + IMG_URL_D + it.imagen + '" onerror="this.onerror=null;this.src=\'' + IMG_DEF_D + '\'" style="width:36px;height:36px;border-radius:6px;object-fit:cover;border:1px solid rgba(255,255,255,.08)">' +
            '<div><p class="mb-0 fw-600" style="color:#fff;font-size:.85rem">' + it.nombre + '</p>' +
            '<p class="mb-0" style="color:#9899aa;font-size:.75rem">' + it.marca + '</p></div></div></td>' +
            '<td style="text-align:center;color:#ccc">' + it.cantidad + '</td>' +
            '<td style="text-align:right;color:#ccc">' + fmt(pu) + '</td>' +
            '<td style="text-align:right;font-weight:700;color:#fff">' + fmt(it.subtotal) + '</td>';
        tbody.appendChild(tr);
    });

    var fmt = function(n) { return '$' + parseFloat(n).toLocaleString('en-US',{minimumFractionDigits:2}); };
    var iva = p.total * 0.13;
    document.getElementById('mp_sub').textContent   = fmt(p.total);
    document.getElementById('mp_iva').textContent   = fmt(iva);
    document.getElementById('mp_total').textContent = fmt(p.total + iva);

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPedido')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/toasts.js"></script>
<?php include '../../includes/flash_toast.php'; ?>
<script src="../../assets/js/main.js"></script>
</body>
</html>
