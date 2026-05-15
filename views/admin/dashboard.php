<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — AutoZone</title>
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

// ── Endpoint AJAX: devolver estadísticas en JSON ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'stats') {
    header('Content-Type: application/json; charset=utf-8');
    $stats = [
        'ingresos'  => (float)$db->query('SELECT COALESCE(SUM(total),0) FROM Venta WHERE estado_venta="Pagado" OR estado_venta="Entregado"')->fetchColumn(),
        'ventas'    => (int)$db->query('SELECT COUNT(*) FROM Venta')->fetchColumn(),
        'productos' => (int)$db->query('SELECT COUNT(*) FROM Producto WHERE estado=1')->fetchColumn(),
        'clientes'  => (int)$db->query('SELECT COUNT(*) FROM Usuario WHERE rol="cliente"')->fetchColumn(),
        'hoy'       => [
            'ventas'   => (int)$db->query('SELECT COUNT(*) FROM Venta WHERE DATE(fecha) = CURDATE()')->fetchColumn(),
            'ingresos' => (float)$db->query('SELECT COALESCE(SUM(total),0) FROM Venta WHERE DATE(fecha) = CURDATE() AND (estado_venta="Pagado" OR estado_venta="Entregado")')->fetchColumn(),
        ],
        'timestamp' => date('H:i:s'),
    ];
    echo json_encode($stats);
    exit;
}

// Estadísticas iniciales
$totalVentas    = $db->query('SELECT COUNT(*) FROM Venta')->fetchColumn();
$totalIngresos  = $db->query('SELECT COALESCE(SUM(total),0) FROM Venta WHERE estado_venta="Pagado" OR estado_venta="Entregado"')->fetchColumn();
$totalProductos = $db->query('SELECT COUNT(*) FROM Producto WHERE estado=1')->fetchColumn();
$totalUsuarios  = $db->query('SELECT COUNT(*) FROM Usuario WHERE rol="cliente"')->fetchColumn();
$ventasRecientes= $db->query('SELECT v.*, u.nombre FROM Venta v JOIN Usuario u ON v.id_usuario=u.id_usuario ORDER BY v.fecha DESC LIMIT 5')->fetchAll();
$productosBajos = $db->query('SELECT * FROM Producto WHERE stock <= 3 AND estado=1 ORDER BY stock ASC LIMIT 5')->fetchAll();

// Estadísticas de hoy
$ventasHoy     = $db->query('SELECT COUNT(*) FROM Venta WHERE DATE(fecha) = CURDATE()')->fetchColumn();
$ingresosHoy   = $db->query('SELECT COALESCE(SUM(total),0) FROM Venta WHERE DATE(fecha) = CURDATE() AND (estado_venta="Pagado" OR estado_venta="Entregado")')->fetchColumn();
?>

<div class="admin-layout">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Dashboard</h1>
                <p class="admin-breadcrumb">Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="dash-live-badge" id="liveBadge" title="Datos actualizados automáticamente">
                    <span class="dash-live-dot"></span> EN VIVO
                </span>
                <span class="admin-date"><?= date('d \d\e F, Y') ?></span>
            </div>
        </div>


        <div class="admin-content">
            <!-- STATS CARDS -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-red">
                        <div class="stat-icon-wrap stat-icon-red">
                            <span class="stat-icon-inner">💰</span>
                        </div>
                        <div class="stat-body">
                            <span class="stat-value" id="statIngresos" data-value="<?= $totalIngresos ?>">$<?= number_format($totalIngresos, 0, '.', ',') ?></span>
                            <span class="stat-label">Ingresos Totales</span>
                            <span class="stat-sub" id="statIngresosHoy">
                                <?php if ($ingresosHoy > 0): ?>
                                    <span class="stat-up">↑</span> $<?= number_format($ingresosHoy, 0, '.', ',') ?> hoy
                                <?php else: ?>
                                    Sin ingresos hoy
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-blue">
                        <div class="stat-icon-wrap stat-icon-blue">
                            <span class="stat-icon-inner">🛒</span>
                        </div>
                        <div class="stat-body">
                            <span class="stat-value" id="statVentas" data-value="<?= $totalVentas ?>"><?= $totalVentas ?></span>
                            <span class="stat-label">Ventas Realizadas</span>
                            <span class="stat-sub" id="statVentasHoy">
                                <?php if ($ventasHoy > 0): ?>
                                    <span class="stat-up">↑</span> <?= $ventasHoy ?> hoy
                                <?php else: ?>
                                    Sin ventas hoy
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-gold">
                        <div class="stat-icon-wrap stat-icon-gold">
                            <span class="stat-icon-inner">🚗</span>
                        </div>
                        <div class="stat-body">
                            <span class="stat-value" id="statProductos" data-value="<?= $totalProductos ?>"><?= $totalProductos ?></span>
                            <span class="stat-label">Vehículos Activos</span>
                            <span class="stat-sub">En catálogo</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-green">
                        <div class="stat-icon-wrap stat-icon-green">
                            <span class="stat-icon-inner">👥</span>
                        </div>
                        <div class="stat-body">
                            <span class="stat-value" id="statClientes" data-value="<?= $totalUsuarios ?>"><?= $totalUsuarios ?></span>
                            <span class="stat-label">Clientes Registrados</span>
                            <span class="stat-sub">Rol cliente</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- VENTAS RECIENTES -->
                <div class="col-xl-7">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">Ventas Recientes</h3>
                            <a href="ventas.php" class="admin-card-link">Ver todas →</a>
                        </div>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventasRecientes as $v): ?>
                                    <tr>
                                        <td style="color:#9899aa">#<?= $v['id_venta'] ?></td>
                                        <td><?= htmlspecialchars($v['nombre']) ?></td>
                                        <td class="fw-600">$<?= number_format($v['total'], 2) ?></td>
                                        <td>
                                            <span class="badge-estado <?= strtolower($v['estado_venta']) ?>">
                                                <?= $v['estado_venta'] ?>
                                            </span>
                                        </td>
                                        <td style="color:#9899aa"><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- STOCK BAJO -->
                <div class="col-xl-5">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">⚠️ Stock Bajo</h3>
                            <a href="productos.php" class="admin-card-link">Gestionar →</a>
                        </div>
                        <?php if (empty($productosBajos)): ?>
                        <p class="text-muted text-center py-4">✅ Todos los vehículos tienen stock suficiente</p>
                        <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($productosBajos as $p): ?>
                            <div class="stock-alert-item">
                                <div>
                                    <span class="stock-alert-name"><?= htmlspecialchars($p['nombre']) ?></span>
                                    <span class="stock-alert-brand"><?= htmlspecialchars($p['marca']) ?></span>
                                </div>
                                <span class="stock-badge <?= $p['stock'] == 0 ? 'stock-0' : 'stock-low' ?>">
                                    <?= $p['stock'] ?> uds.
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/toasts.js"></script>
<?php include '../../includes/flash_toast.php'; ?>

<script>
// ============================================
//  DASHBOARD — Estadísticas en Tiempo Real
// ============================================
(function() {
    const REFRESH_INTERVAL = 15000; // 15 segundos
    const liveBadge = document.getElementById('liveBadge');

    // ---- Animación de conteo numérico ----
    function animarConteo(el, valorFinal, prefijo, esMoneda) {
        const valorActual = parseFloat(el.dataset.value) || 0;
        if (valorActual === valorFinal) return;

        el.dataset.value = valorFinal;
        const duracion = 800;
        const inicio = performance.now();

        function step(ts) {
            const progreso = Math.min((ts - inicio) / duracion, 1);
            const ease = 1 - Math.pow(1 - progreso, 3); // easeOutCubic
            const val = valorActual + (valorFinal - valorActual) * ease;

            if (esMoneda) {
                el.textContent = prefijo + Math.round(val).toLocaleString('en-US');
            } else {
                el.textContent = prefijo + Math.round(val);
            }

            if (progreso < 1) {
                requestAnimationFrame(step);
            } else {
                // Resaltar brevemente si cambió
                if (valorActual !== valorFinal) {
                    el.classList.add('stat-value-changed');
                    setTimeout(() => el.classList.remove('stat-value-changed'), 1200);
                }
            }
        }
        requestAnimationFrame(step);
    }

    // ---- Formatear moneda ----
    function fmtMoneda(v) {
        return '$' + Math.round(v).toLocaleString('en-US');
    }

    // ---- Actualizar estadísticas via AJAX ----
    function refrescarStats() {
        // Parpadeo del badge "EN VIVO"
        if (liveBadge) {
            liveBadge.classList.add('dash-live-pulse');
            setTimeout(() => liveBadge.classList.remove('dash-live-pulse'), 1000);
        }

        fetch('dashboard.php?ajax=stats')
            .then(r => r.json())
            .then(data => {
                // Animar stat cards
                const elIngresos  = document.getElementById('statIngresos');
                const elVentas    = document.getElementById('statVentas');
                const elProductos = document.getElementById('statProductos');
                const elClientes  = document.getElementById('statClientes');

                if (elIngresos)  animarConteo(elIngresos,  data.ingresos,  '$', true);
                if (elVentas)    animarConteo(elVentas,    data.ventas,    '',  false);
                if (elProductos) animarConteo(elProductos, data.productos, '',  false);
                if (elClientes)  animarConteo(elClientes,  data.clientes,  '',  false);

                // Actualizar sub-indicadores de hoy
                const subIngresos = document.getElementById('statIngresosHoy');
                const subVentas   = document.getElementById('statVentasHoy');

                if (subIngresos) {
                    subIngresos.innerHTML = data.hoy.ingresos > 0
                        ? '<span class="stat-up">↑</span> ' + fmtMoneda(data.hoy.ingresos) + ' hoy'
                        : 'Sin ingresos hoy';
                }
                if (subVentas) {
                    subVentas.innerHTML = data.hoy.ventas > 0
                        ? '<span class="stat-up">↑</span> ' + data.hoy.ventas + ' hoy'
                        : 'Sin ventas hoy';
                }
            })
            .catch(() => {
                // Silencioso en caso de error de red
            });
    }

    // ---- Conteo inicial con animación al cargar ----
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.stat-value[data-value]');
        cards.forEach(function(el) {
            const val     = parseFloat(el.dataset.value) || 0;
            const esMon   = el.textContent.trim().startsWith('$');
            el.dataset.value = 0;
            setTimeout(() => animarConteo(el, val, esMon ? '$' : '', esMon), 300);
        });
    });

    // ---- Auto-refresh cada 15 segundos ----
    setInterval(refrescarStats, REFRESH_INTERVAL);
})();
</script>
</body>
</html>
