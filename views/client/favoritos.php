<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos — AutoZone</title>
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
$db = getDB();
$flash = getFlash();

$stmt = $db->prepare('
    SELECT p.*, c.nombre_categoria, f.fecha AS fecha_fav
    FROM Favorito f
    JOIN Producto p ON f.id_producto = p.id_producto
    JOIN Categoria c ON p.id_categoria = c.id_categoria
    WHERE f.id_usuario = ?
    ORDER BY f.fecha DESC
');
$stmt->execute([$_SESSION['id_usuario']]);
$favoritos = $stmt->fetchAll();
?>

<nav class="client-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="../../index.php" class="brand-logo" style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:#fff;text-decoration:none;">
            <span style="color:#e8272b">⬡</span> AUTO<span style="color:#e8272b">ZONE</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="catalogo.php" class="nav-link text-white-50">Catálogo</a>
            <a href="carrito.php"  class="nav-link text-white">🛒 Carrito (<?= totalCarrito() ?>)</a>
            <a href="../../controllers/AuthController.php?accion=logout" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="client-main">
    <div class="container py-5">
        <h1 class="section-title mb-2">♥ Mis Favoritos</h1>
        <p class="text-muted mb-5"><?= count($favoritos) ?> vehículos guardados</p>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mb-4">
            <?= htmlspecialchars($flash['mensaje']) ?>
        </div>
        <?php endif; ?>

        <?php if (empty($favoritos)): ?>
        <div class="text-center py-5 admin-card">
            <p style="font-size:4rem">♡</p>
            <h3 class="text-white mb-3">Aún no tienes favoritos</h3>
            <p class="text-muted mb-4">Guarda los vehículos que más te gustan desde el catálogo.</p>
            <a href="catalogo.php" class="btn btn-accent px-5">Explorar Catálogo</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favoritos as $p): ?>
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="product-card">
                    <div class="product-img-wrap">
                        <img src="../../assets/img/<?= htmlspecialchars($p['imagen']) ?>"
                             onerror="this.src='../../assets/img/default.jpg'"
                             alt="<?= htmlspecialchars($p['nombre']) ?>"
                             class="product-img">
                        <span class="product-badge"><?= htmlspecialchars($p['nombre_categoria']) ?></span>
                    </div>
                    <div class="product-info">
                        <p class="product-brand"><?= htmlspecialchars($p['marca']) ?></p>
                        <h3 class="product-name"><?= htmlspecialchars($p['nombre']) ?></h3>
                        <p class="product-desc"><?= htmlspecialchars(substr($p['descripcion'],0,80)) ?>...</p>
                        <div class="product-footer">
                            <span class="product-price">$<?= number_format($p['precio'],0,'.',',') ?></span>
                            <div class="product-actions">
                                <!-- Quitar favorito -->
                                <form method="POST" action="../../controllers/ProductoController.php" class="d-inline">
                                    <input type="hidden" name="accion" value="toggle_favorito">
                                    <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                    <button type="submit" class="btn-action btn-fav" title="Quitar favorito">♥</button>
                                </form>
                                <!-- Agregar al carrito -->
                                <?php if ($p['stock'] > 0): ?>
                                <form method="POST" action="../../controllers/ProductoController.php" class="d-inline">
                                    <input type="hidden" name="accion" value="agregar_carrito">
                                    <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                    <input type="hidden" name="cantidad" value="1">
                                    <button type="submit" class="btn-action btn-cart">🛒</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
