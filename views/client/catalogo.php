<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo — AutoZone</title>
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

$categoriaFiltro = (int)($_GET['categoria'] ?? 0);
$marcaFiltro     = sanitizar($_GET['marca'] ?? '');
$busqueda        = sanitizar($_GET['q']     ?? '');

// Construir query con filtros
$where  = ['p.estado = 1'];
$params = [];
if ($categoriaFiltro > 0) { $where[] = 'p.id_categoria = ?'; $params[] = $categoriaFiltro; }
if ($marcaFiltro)         { $where[] = 'p.marca = ?';         $params[] = $marcaFiltro; }
if ($busqueda)            { $where[] = '(p.nombre LIKE ? OR p.marca LIKE ? OR c.nombre_categoria LIKE ?)';
                            $params = array_merge($params, ["%$busqueda%","%$busqueda%","%$busqueda%"]); }

$sql     = 'SELECT p.*, c.nombre_categoria FROM Producto p JOIN Categoria c ON p.id_categoria=c.id_categoria';
$sql    .= ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id_producto DESC';
$stmt    = $db->prepare($sql);
$stmt->execute($params);
$productos  = $stmt->fetchAll();
$categorias = $db->query('SELECT * FROM Categoria ORDER BY nombre_categoria')->fetchAll();
$marcas     = $db->query('SELECT DISTINCT marca FROM Producto WHERE estado=1 ORDER BY marca')->fetchAll(PDO::FETCH_COLUMN);

// Favoritos del usuario actual
$favs = [];
$stmtF = $db->prepare('SELECT id_producto FROM Favorito WHERE id_usuario = ?');
$stmtF->execute([$_SESSION['id_usuario']]);
$favs  = array_column($stmtF->fetchAll(), 'id_producto');

$carritoTotal = totalCarrito();
?>

<!-- NAVBAR CLIENTE -->
<nav class="client-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="../../index.php" class="brand-logo" style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:#fff;text-decoration:none;">
            <span style="color:#e8272b">⬡</span> AUTO<span style="color:#e8272b">ZONE</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="favoritos.php" class="nav-link text-white-50">♡ Favoritos (<?= count($favs) ?>)</a>
            <a href="carrito.php" class="position-relative nav-link text-white">
                🛒 Carrito
                <?php if ($carritoTotal > 0): ?>
                <span class="cart-badge"><?= $carritoTotal ?></span>
                <?php endif; ?>
            </a>
            <span class="text-white-50 small"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="../../controllers/AuthController.php?accion=logout" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="client-main">
    <div class="container py-5">
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mb-4">
            <?= htmlspecialchars($flash['mensaje']) ?>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- FILTROS SIDEBAR -->
            <div class="col-lg-3">
                <div class="admin-card">
                    <h3 class="admin-card-title mb-4">🔍 Filtros</h3>
                    <form method="GET" id="filterForm">
                        <div class="mb-4">
                            <label class="auth-label">Búsqueda</label>
                            <input type="text" name="q" class="auth-input" placeholder="Nombre, marca..."
                                   value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        <div class="mb-4">
                            <label class="auth-label">Categoría</label>
                            <select name="categoria" class="auth-input">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>" <?= $categoriaFiltro == $c['id_categoria'] ? 'selected':'' ?>>
                                    <?= htmlspecialchars($c['nombre_categoria']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="auth-label">Marca</label>
                            <select name="marca" class="auth-input">
                                <option value="">Todas</option>
                                <?php foreach ($marcas as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= $marcaFiltro === $m ? 'selected':'' ?>>
                                    <?= htmlspecialchars($m) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-accent w-100 mb-2">Aplicar Filtros</button>
                        <a href="catalogo.php" class="btn btn-outline-secondary w-100">Limpiar</a>
                    </form>
                </div>
            </div>

            <!-- PRODUCTOS -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title" style="font-size:1.8rem">
                        Catálogo
                        <?php if ($busqueda || $categoriaFiltro || $marcaFiltro): ?>
                        <span class="section-subtitle" style="font-size:1rem"> — <?= count($productos) ?> resultados</span>
                        <?php endif; ?>
                    </h2>
                </div>

                <?php if (empty($productos)): ?>
                <div class="text-center py-5">
                    <p style="font-size:3rem">🔍</p>
                    <p class="text-muted">No se encontraron vehículos con los filtros seleccionados.</p>
                    <a href="catalogo.php" class="btn btn-accent mt-3">Ver todo el catálogo</a>
                </div>
                <?php else: ?>
                <div class="row g-4" id="productosGrid">
                    <?php foreach ($productos as $p): ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="product-card">
                            <div class="product-img-wrap">
                                <img src="../../assets/img/<?= htmlspecialchars($p['imagen']) ?>"
                                     onerror="this.src='../../assets/img/default.jpg'"
                                     alt="<?= htmlspecialchars($p['nombre']) ?>"
                                     class="product-img">
                                <span class="product-badge"><?= htmlspecialchars($p['nombre_categoria']) ?></span>
                                <span class="product-stock <?= $p['stock'] > 0 ? 'in-stock' : 'no-stock' ?>">
                                    <?= $p['stock'] > 0 ? '✓ Disponible' : '✗ Agotado' ?>
                                </span>
                            </div>
                            <div class="product-info">
                                <p class="product-brand"><?= htmlspecialchars($p['marca']) ?></p>
                                <h3 class="product-name"><?= htmlspecialchars($p['nombre']) ?></h3>
                                <p class="product-desc"><?= htmlspecialchars(substr($p['descripcion'],0,80)) ?>...</p>
                                <div class="product-footer">
                                    <span class="product-price">$<?= number_format($p['precio'],0,'.',',') ?></span>
                                    <div class="product-actions">
                                        <!-- Favorito -->
                                        <form method="POST" action="../../controllers/ProductoController.php" class="d-inline">
                                            <input type="hidden" name="accion" value="toggle_favorito">
                                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                            <button type="submit" class="btn-action btn-fav" title="<?= in_array($p['id_producto'],$favs) ? 'Quitar favorito' : 'Agregar favorito' ?>">
                                                <?= in_array($p['id_producto'],$favs) ? '♥' : '♡' ?>
                                            </button>
                                        </form>
                                        <!-- Carrito -->
                                        <?php if ($p['stock'] > 0): ?>
                                        <form method="POST" action="../../controllers/ProductoController.php" class="d-inline">
                                            <input type="hidden" name="accion" value="agregar_carrito">
                                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                            <input type="hidden" name="cantidad" value="1">
                                            <button type="submit" class="btn-action btn-cart" title="Agregar al carrito">🛒</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="btn-action" style="opacity:0.4;cursor:not-allowed" title="Sin stock">🛒</span>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
