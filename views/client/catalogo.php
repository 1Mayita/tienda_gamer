<?php
// ── PHP PRIMERO — antes de cualquier salida HTML ────────────────
require_once '../../config/database.php';
require_once '../../includes/funciones.php';
protegerRuta();
$db    = getDB();
$flash = getFlash();

$categoriaFiltro = (int)($_GET['categoria'] ?? 0);
$marcaFiltro     = sanitizar($_GET['marca'] ?? '');
$busqueda        = sanitizar($_GET['q']     ?? '');

$where  = ['p.estado = 1'];
$params = [];
if ($categoriaFiltro > 0) {
    $where[] = 'p.id_categoria = ?';
    $params[] = $categoriaFiltro;
}
if ($marcaFiltro) {
    $where[] = 'p.marca = ?';
    $params[] = $marcaFiltro;
}
if ($busqueda) {
    $where[]  = '(p.nombre LIKE ? OR p.marca LIKE ? OR c.nombre_categoria LIKE ?)';
    $params   = array_merge($params, ["%$busqueda%", "%$busqueda%", "%$busqueda%"]);
}

$sql  = 'SELECT p.*, c.nombre_categoria FROM Producto p JOIN Categoria c ON p.id_categoria=c.id_categoria';
$sql .= ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id_producto DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$productos  = $stmt->fetchAll();
$categorias = $db->query('SELECT * FROM Categoria ORDER BY nombre_categoria')->fetchAll();
$marcas     = $db->query('SELECT DISTINCT marca FROM Producto WHERE estado=1 ORDER BY marca')->fetchAll(PDO::FETCH_COLUMN);

// Mapa categoria_id → marcas para JS
$marcasPorCat = [];
foreach ($db->query('SELECT DISTINCT id_categoria, marca FROM Producto WHERE estado=1 ORDER BY marca')->fetchAll() as $r) {
    $marcasPorCat[$r['id_categoria']][] = $r['marca'];
}

$stmtF = $db->prepare('SELECT id_producto FROM Favorito WHERE id_usuario = ?');
$stmtF->execute([$_SESSION['id_usuario']]);
$favs = array_column($stmtF->fetchAll(), 'id_producto');

$carritoTotal = totalCarrito();

// Datos para modal de detalle (JS)
$productosJS = [];
foreach ($productos as $p) {
    $productosJS[$p['id_producto']] = [
        'id'          => $p['id_producto'],
        'nombre'      => $p['nombre'],
        'marca'       => $p['marca'],
        'categoria'   => $p['nombre_categoria'],
        'descripcion' => $p['descripcion'],
        'precio'      => $p['precio'],
        'stock'       => $p['stock'],
        'imagen'      => IMG_PRODUCTOS_URL . $p['imagen'],
        'imgDefault'  => IMG_DEFAULT_URL,
        'esFav'       => in_array($p['id_producto'], $favs),
    ];
}
?>
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
    <style>
        /* Modal de detalle */
        .modal-detalle .modal-content { background:#0f0f18; border:1px solid #1e1e2e; border-radius:16px; }
        .modal-detalle .modal-header  { border-bottom:1px solid #1e1e2e; padding:1.25rem 1.5rem; }
        .modal-detalle .modal-body    { padding:0; }
        .det-img-wrap      { position:relative; height:320px; overflow:hidden; background:#0a0a12; }
        .det-img-wrap img  { width:100%; height:100%; object-fit:cover; }
        .det-img-overlay   { position:absolute; inset:0; background:linear-gradient(to top,#0f0f18 0%,transparent 60%); }
        .det-body          { padding:1.75rem; }
        .det-brand         { font-size:.75rem; font-weight:700; letter-spacing:2px; color:#e8272b; text-transform:uppercase; margin-bottom:.4rem; }
        .det-name          { font-family:'Bebas Neue',sans-serif; font-size:2.2rem; color:#fff; line-height:1; margin-bottom:.5rem; }
        .det-cat           { display:inline-block; font-size:.7rem; font-weight:600; letter-spacing:1px; text-transform:uppercase;
                             background:rgba(232,39,43,.12); color:#e8272b; border:1px solid rgba(232,39,43,.25);
                             padding:.25rem .65rem; border-radius:20px; margin-bottom:1rem; }
        .det-desc          { font-size:.9rem; color:#aaa; line-height:1.7; margin-bottom:1.5rem; }
        .det-stats         { display:flex; gap:1.5rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .det-stat          { text-align:center; }
        .det-stat-val      { display:block; font-family:'Bebas Neue',sans-serif; font-size:1.5rem; color:#fff; }
        .det-stat-lbl      { display:block; font-size:.7rem; color:#666; text-transform:uppercase; letter-spacing:1px; margin-top:-.2rem; }
        .det-divider       { border-color:rgba(255,255,255,.06); margin:1.25rem 0; }
        .det-price         { font-family:'Bebas Neue',sans-serif; font-size:2.6rem; color:#fff; }
        .det-price small   { font-size:1rem; color:#666; font-family:'Inter',sans-serif; font-weight:400; }
        .det-stock-ok      { color:#22c55e; font-size:.82rem; font-weight:600; }
        .det-stock-no      { color:#e8272b; font-size:.82rem; font-weight:600; }
        .det-actions       { display:flex; gap:.75rem; margin-top:1.25rem; flex-wrap:wrap; }
        .det-actions .btn  { flex:1; min-width:120px; }

        /* Tarjeta clicable */
        .product-card { cursor:pointer; transition:transform .22s; }
        .product-card:hover { transform:translateY(-5px); box-shadow:0 12px 40px rgba(0,0,0,.5); }

    </style>
</head>
<body class="admin-body">

<!-- NAVBAR -->
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
            <a href="dashboard.php" class="nav-link" style="color:#e8272b;font-weight:600">📦 Mis Pedidos</a>
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
            <!-- FILTROS -->
            <div class="col-lg-3">
                <div class="admin-card">
                    <h3 class="admin-card-title mb-4">🔍 Filtros</h3>
                    <form method="GET" id="filterForm">
                        <div class="mb-3">
                            <label class="auth-label">Búsqueda</label>
                            <input type="text" name="q" id="fBusqueda" class="auth-input"
                                   placeholder="Nombre, marca..."
                                   value="<?= htmlspecialchars($busqueda) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="auth-label">Categoría</label>
                            <select name="categoria" id="fCategoria" class="auth-input">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>"
                                        <?= $categoriaFiltro == $c['id_categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre_categoria']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="auth-label">Marca</label>
                            <select name="marca" id="fMarca" class="auth-input">
                                <option value="">Todas las marcas</option>
                                <?php foreach ($marcas as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>"
                                        <?= $marcaFiltro === $m ? 'selected' : '' ?>>
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
                        <div class="product-card" data-id="<?= $p['id_producto'] ?>">
                            <div class="product-img-wrap">
                                <img src="<?= IMG_PRODUCTOS_URL . htmlspecialchars($p['imagen']) ?>"
                                     onerror="this.onerror=null;this.src='<?= IMG_DEFAULT_URL ?>'"
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
                                <p class="product-desc"><?= htmlspecialchars(substr($p['descripcion'], 0, 75)) ?>...</p>
                                <div class="product-footer">
                                    <span class="product-price">$<?= number_format($p['precio'], 0, '.', ',') ?></span>
                                    <div class="product-actions">
                                        <form method="POST" action="../../controllers/ProductoController.php" class="d-inline"
                                              onclick="event.stopPropagation()">
                                            <input type="hidden" name="accion" value="toggle_favorito">
                                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                            <button type="submit" class="btn-action btn-fav"
                                                    title="<?= in_array($p['id_producto'], $favs) ? 'Quitar favorito' : 'Agregar favorito' ?>">
                                                <?= in_array($p['id_producto'], $favs) ? '♥' : '♡' ?>
                                            </button>
                                        </form>
                                        <?php if ($p['stock'] > 0): ?>
                                        <form method="POST" action="../../controllers/ProductoController.php" class="d-inline"
                                              onclick="event.stopPropagation()">
                                            <input type="hidden" name="accion" value="agregar_carrito">
                                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                            <input type="hidden" name="cantidad" value="1">
                                            <button type="submit" class="btn-action btn-cart" title="Agregar al carrito">🛒</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="btn-action" style="opacity:0.4;cursor:not-allowed">🛒</span>
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

<!-- MODAL DE DETALLE -->
<div class="modal fade modal-detalle" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="det-img-wrap">
                    <img id="detImg" src="" alt="">
                    <div class="det-img-overlay"></div>
                </div>
                <div class="det-body">
                    <p class="det-brand" id="detMarca"></p>
                    <h2 class="det-name" id="detNombre"></h2>
                    <span class="det-cat" id="detCategoria"></span>
                    <p class="det-desc" id="detDesc"></p>
                    <div class="det-stats">
                        <div class="det-stat">
                            <span class="det-stat-val" id="detPrecio"></span>
                            <span class="det-stat-lbl">Precio USD</span>
                        </div>
                        <div class="det-stat">
                            <span class="det-stat-val" id="detStock"></span>
                            <span class="det-stat-lbl">En stock</span>
                        </div>
                    </div>
                    <hr class="det-divider">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="det-price"><small>USD</small> <span id="detPrecioGrande"></span></div>
                            <div id="detDisponibilidad"></div>
                        </div>
                        <div class="det-actions" id="detAcciones"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (antes del código personalizado) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>

<script>
const PRODUCTOS      = <?= json_encode($productosJS,  JSON_UNESCAPED_UNICODE) ?>;
const MARCAS_POR_CAT = <?= json_encode($marcasPorCat, JSON_UNESCAPED_UNICODE) ?>;
const MARCA_ACTUAL   = <?= json_encode($marcaFiltro) ?>;

// ── Clic en tarjeta (event delegation) ──────────────────
document.getElementById('productosGrid')?.addEventListener('click', function (e) {
    if (e.target.closest('form')) return;
    const card = e.target.closest('.product-card');
    if (card) abrirDetalle(parseInt(card.dataset.id));
});

function abrirDetalle(id) {
    const p = PRODUCTOS[id];
    if (!p) return;

    const img = document.getElementById('detImg');
    img.src = p.imagen;
    img.onerror = () => { img.onerror = null; img.src = p.imgDefault; };
    img.alt = p.nombre;

    document.getElementById('detMarca').textContent      = p.marca;
    document.getElementById('detNombre').textContent     = p.nombre;
    document.getElementById('detCategoria').textContent  = p.categoria;
    document.getElementById('detDesc').textContent       = p.descripcion;

    const fmt = new Intl.NumberFormat('en-US').format(p.precio);
    document.getElementById('detPrecio').textContent       = '$' + fmt;
    document.getElementById('detPrecioGrande').textContent = fmt;
    document.getElementById('detStock').textContent        = p.stock;

    document.getElementById('detDisponibilidad').innerHTML = p.stock > 0
        ? '<span class="det-stock-ok">✓ Disponible en stock</span>'
        : '<span class="det-stock-no">✗ Sin stock disponible</span>';

    const fav = p.esFav ? '♥ En Favoritos' : '♡ Favorito';
    let html = `<form method="POST" action="../../controllers/ProductoController.php" class="d-inline">
        <input type="hidden" name="accion" value="toggle_favorito">
        <input type="hidden" name="id_producto" value="${id}">
        <button type="submit" class="btn btn-outline-light">${fav}</button></form>`;

    if (p.stock > 0) {
        html += `<form method="POST" action="../../controllers/ProductoController.php" class="d-inline">
            <input type="hidden" name="accion" value="agregar_carrito">
            <input type="hidden" name="id_producto" value="${id}">
            <input type="hidden" name="cantidad" value="1">
            <button type="submit" class="btn btn-accent">🛒 Agregar al carrito</button></form>`;
    } else {
        html += `<button class="btn btn-secondary" disabled>Sin stock</button>`;
    }
    document.getElementById('detAcciones').innerHTML = html;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalle')).show();
}

// ── Filtros dinámicos ────────────────────────────────────
(function () {
    const selCat   = document.getElementById('fCategoria');
    const selMarca = document.getElementById('fMarca');
    if (!selCat || !selMarca) return;

    function actualizarMarcas() {
        const catId = selCat.value;
        const marcas = catId ? (MARCAS_POR_CAT[catId] || []) : null;
        const prev   = selMarca.value;
        selMarca.innerHTML = '<option value="">Todas las marcas</option>';
        const lista = marcas ?? Object.values(MARCAS_POR_CAT).flat()
                                     .filter((v,i,a) => a.indexOf(v)===i).sort();
        lista.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m; opt.textContent = m;
            if (m === (marcas ? prev : MARCA_ACTUAL)) opt.selected = true;
            selMarca.appendChild(opt);
        });
    }

    selCat.addEventListener('change', () => { actualizarMarcas(); document.getElementById('filterForm').submit(); });
    selMarca.addEventListener('change', () => document.getElementById('filterForm').submit());
    actualizarMarcas();
}());
</script>
</body>
</html>
