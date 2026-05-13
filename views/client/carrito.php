<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras — AutoZone</title>
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
iniciarSesion();

$flash   = getFlash();
$carrito = $_SESSION['carrito'] ?? [];

// Manejo de acciones del carrito vía GET
$accion = $_GET['accion'] ?? '';
if ($accion === 'eliminar' && isset($_GET['id'])) {
    unset($_SESSION['carrito'][(int)$_GET['id']]);
    $carrito = $_SESSION['carrito'];
    header('Location: carrito.php');
    exit;
}
if ($accion === 'vaciar') {
    unset($_SESSION['carrito']);
    header('Location: carrito.php');
    exit;
}

// Obtener datos de productos del carrito
$items = [];
$total = 0.0;
if (!empty($carrito)) {
    $db = getDB();
    foreach ($carrito as $id => $cant) {
        $s = $db->prepare('SELECT * FROM Producto WHERE id_producto = ? AND estado = 1');
        $s->execute([$id]);
        $p = $s->fetch();
        if ($p) {
            $sub     = $p['precio'] * $cant;
            $total  += $sub;
            $items[] = ['producto' => $p, 'cantidad' => $cant, 'subtotal' => $sub];
        }
    }
}
?>

<!-- NAVBAR CLIENTE -->
<nav class="client-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="../../index.php" class="brand-logo" style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:#fff;text-decoration:none;">
            <span style="color:#e8272b">⬡</span> AUTO<span style="color:#e8272b">ZONE</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="catalogo.php"  class="nav-link text-white-50">Catálogo</a>
            <a href="favoritos.php" class="nav-link text-white-50">♡ Favoritos</a>
            <span class="text-white-50 small"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="../../controllers/AuthController.php?accion=logout" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="client-main">
    <div class="container py-5">
        <h1 class="section-title mb-5">🛒 Mi Carrito</h1>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mb-4">
            <?= htmlspecialchars($flash['mensaje']) ?>
        </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
        <div class="text-center py-5 admin-card">
            <p style="font-size:4rem">🛒</p>
            <h3 class="text-white mb-3">Tu carrito está vacío</h3>
            <p class="text-muted mb-4">Agrega vehículos desde nuestro catálogo.</p>
            <a href="catalogo.php" class="btn btn-accent px-5">Explorar Catálogo</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="admin-card p-0">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Vehículo</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item):
                                $p = $item['producto']; ?>
                            <tr class="cart-item-row">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="../../assets/img/<?= htmlspecialchars($p['imagen']) ?>"
                                             onerror="this.src='../../assets/img/default.jpg'"
                                             class="cart-img" alt="">
                                        <div>
                                            <p class="fw-600 mb-0" style="color:#fff"><?= htmlspecialchars($p['nombre']) ?></p>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($p['marca']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-600" data-price="<?= $p['precio'] ?>">
                                    $<?= number_format($p['precio'], 2) ?>
                                </td>
                                <td>
                                    <div class="qty-wrap">
                                        <button type="button" class="qty-btn" data-action="minus">−</button>
                                        <input type="number" class="qty-input"
                                               value="<?= $item['cantidad'] ?>"
                                               min="1" max="<?= $p['stock'] ?>"
                                               data-max="<?= $p['stock'] ?>">
                                        <button type="button" class="qty-btn" data-action="plus">+</button>
                                    </div>
                                </td>
                                <td class="fw-600">
                                    <span class="subtotal-val">$<?= number_format($item['subtotal'], 2) ?></span>
                                </td>
                                <td>
                                    <a href="carrito.php?accion=eliminar&id=<?= $p['id_producto'] ?>"
                                       class="btn-table-action btn-delete"
                                       onclick="return confirm('¿Eliminar del carrito?')"
                                       title="Eliminar">🗑️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex gap-3">
                    <a href="catalogo.php" class="btn btn-outline-secondary">← Seguir comprando</a>
                    <a href="carrito.php?accion=vaciar" class="btn btn-outline-danger"
                       onclick="return confirm('¿Vaciar el carrito?')">Vaciar carrito</a>
                </div>
            </div>

            <!-- RESUMEN -->
            <div class="col-lg-4">
                <div class="admin-card">
                    <h3 class="admin-card-title mb-4">Resumen del Pedido</h3>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-600 text-white">$<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">IVA (13%)</span>
                        <span class="fw-600 text-white">$<?= number_format($total * 0.13, 2) ?></span>
                    </div>
                    <hr style="border-color:rgba(255,255,255,.08)">
                    <div class="d-flex justify-content-between mb-4">
                        <span style="font-size:1.1rem;font-weight:700;color:#fff">Total</span>
                        <span id="cartTotal" style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;color:#fff">
                            $<?= number_format($total * 1.13, 2) ?>
                        </span>
                    </div>
                    <form method="POST" action="../../controllers/ProductoController.php">
                        <input type="hidden" name="accion" value="confirmar_compra">
                        <button type="submit" class="btn btn-accent w-100 py-3">
                            Confirmar Compra →
                        </button>
                    </form>
                    <p class="text-muted small text-center mt-3">
                        🔒 Pago seguro y protegido
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
