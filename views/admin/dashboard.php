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

// Estadísticas
$totalVentas    = $db->query('SELECT COUNT(*) FROM Venta')->fetchColumn();
$totalIngresos  = $db->query('SELECT COALESCE(SUM(total),0) FROM Venta WHERE estado_venta="Pagado" OR estado_venta="Entregado"')->fetchColumn();
$totalProductos = $db->query('SELECT COUNT(*) FROM Producto WHERE estado=1')->fetchColumn();
$totalUsuarios  = $db->query('SELECT COUNT(*) FROM Usuario WHERE rol="cliente"')->fetchColumn();
$ventasRecientes= $db->query('SELECT v.*, u.nombre FROM Venta v JOIN Usuario u ON v.id_usuario=u.id_usuario ORDER BY v.fecha DESC LIMIT 5')->fetchAll();
$productosBajos = $db->query('SELECT * FROM Producto WHERE stock <= 3 AND estado=1 ORDER BY stock ASC LIMIT 5')->fetchAll();
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
                <span class="admin-date"><?= date('d \d\e F, Y') ?></span>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mx-4 mt-3">
            <?= htmlspecialchars($flash['mensaje']) ?>
        </div>
        <?php endif; ?>

        <div class="admin-content">
            <!-- STATS CARDS -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-red">
                        <div class="stat-icon">💰</div>
                        <div class="stat-body">
                            <span class="stat-value">$<?= number_format($totalIngresos, 0, '.', ',') ?></span>
                            <span class="stat-label">Ingresos Totales</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-blue">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-body">
                            <span class="stat-value"><?= $totalVentas ?></span>
                            <span class="stat-label">Ventas Realizadas</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-gold">
                        <div class="stat-icon">🚗</div>
                        <div class="stat-body">
                            <span class="stat-value"><?= $totalProductos ?></span>
                            <span class="stat-label">Vehículos Activos</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-green">
                        <div class="stat-icon">👥</div>
                        <div class="stat-body">
                            <span class="stat-value"><?= $totalUsuarios ?></span>
                            <span class="stat-label">Clientes Registrados</span>
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
                                        <td class="text-muted">#<?= $v['id_venta'] ?></td>
                                        <td><?= htmlspecialchars($v['nombre']) ?></td>
                                        <td class="fw-600">$<?= number_format($v['total'], 2) ?></td>
                                        <td>
                                            <span class="badge-estado <?= strtolower($v['estado_venta']) ?>">
                                                <?= $v['estado_venta'] ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
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
</body>
</html>
