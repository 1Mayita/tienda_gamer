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

$pedidos = $db->prepare('SELECT v.*, COUNT(dv.id_detalle) as num_items FROM Venta v LEFT JOIN Detalle_Venta dv ON v.id_venta=dv.id_venta WHERE v.id_usuario=? GROUP BY v.id_venta ORDER BY v.fecha DESC');
$pedidos->execute([$id]);
$pedidos = $pedidos->fetchAll();
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
                <p class="text-muted"><?= htmlspecialchars($_SESSION['correo']) ?></p>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mb-4">
            <?= htmlspecialchars($flash['mensaje']) ?>
        </div>
        <?php endif; ?>

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
            <p class="text-muted text-center py-4">Aún no has realizado ningún pedido.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Artículos</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td class="fw-600 text-white">#<?= $p['id_venta'] ?></td>
                            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                            <td><?= $p['num_items'] ?> ítem(s)</td>
                            <td class="fw-600 text-white">$<?= number_format($p['total'],2) ?></td>
                            <td>
                                <span class="badge-estado <?= strtolower($p['estado_venta']) ?>">
                                    <?= $p['estado_venta'] ?>
                                </span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
