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
    setFlash('success', 'Producto eliminado del carrito.');
    header('Location: carrito.php');
    exit;
}
if ($accion === 'vaciar') {
    unset($_SESSION['carrito']);
    setFlash('success', 'El carrito ha sido vaciado.');
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
            <a href="dashboard.php" class="nav-link" style="color:#e8272b;font-weight:600">📦 Mis Pedidos</a>
            <span class="text-white-50 small"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="../../controllers/AuthController.php?accion=logout" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="client-main">
    <div class="container py-5">
        <h1 class="section-title mb-5">🛒 Mi Carrito</h1>

        <?php // Flash toast se muestra al final del archivo ?>

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
                            <tr class="cart-item-row" data-id="<?= $p['id_producto'] ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= IMG_PRODUCTOS_URL . htmlspecialchars($p['imagen']) ?>"
                                             onerror="this.onerror=null;this.src='<?= IMG_DEFAULT_URL ?>'"
                                             class="cart-img" alt="">
                                        <div>
                                            <p class="fw-600 mb-0" style="color:#fff"><?= htmlspecialchars($p['nombre']) ?></p>
                                            <p class="small mb-0" style="color:#aaa"><?= htmlspecialchars($p['marca']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-600" data-price="<?= $p['precio'] ?>">
                                    $<?= number_format($p['precio'], 2) ?>
                                </td>
                                <td>
                                    <div class="qty-wrap">
                                        <button type="button" class="qty-btn" data-action="minus"
                                                <?= $item['cantidad'] <= 1 ? 'disabled' : '' ?>>−</button>
                                        <input type="number" class="qty-input"
                                               value="<?= $item['cantidad'] ?>"
                                               min="1"
                                               data-stock="<?= $p['stock'] ?>"
                                               data-id="<?= $p['id_producto'] ?>">
                                        <button type="button" class="qty-btn" data-action="plus"
                                                <?= $item['cantidad'] >= $p['stock'] ? 'disabled' : '' ?>>+</button>
                                    </div>
                                    <small class="stock-indicator" style="display:block;margin-top:4px;font-size:.72rem;
                                        color:<?= $p['stock'] <= 3 ? ($p['stock']==0 ? '#e8272b' : '#f59e0b') : '#666' ?>">
                                        <?php if ($p['stock'] <= 3): ?>
                                            ⚠️ Solo <?= $p['stock'] ?> disponible<?= $p['stock']!=1?'s':'' ?>
                                        <?php else: ?>
                                            Stock: <?= $p['stock'] ?> unidades
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="fw-600">
                                    <span class="subtotal-val">$<?= number_format($item['subtotal'], 2) ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn-table-action btn-delete btn-confirmar-eliminar"
                                            data-url="carrito.php?accion=eliminar&id=<?= $p['id_producto'] ?>"
                                            data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                            title="Eliminar">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex gap-3">
                    <a href="catalogo.php" class="btn btn-outline-secondary">← Seguir comprando</a>
                    <button type="button" class="btn btn-outline-danger btn-confirmar-vaciar">Vaciar carrito</button>
                </div>
            </div>

            <!-- RESUMEN -->
            <div class="col-lg-4">
                <div class="admin-card">
                    <h3 class="admin-card-title mb-4">Resumen del Pedido</h3>
                    <div class="d-flex justify-content-between mb-3">
                        <span style="color:#bbb">Subtotal</span>
                        <span class="fw-600 text-white">$<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span style="color:#bbb">IVA (13%)</span>
                        <span class="fw-600 text-white">$<?= number_format($total * 0.13, 2) ?></span>
                    </div>
                    <hr style="border-color:rgba(255,255,255,.08)">
                    <div class="d-flex justify-content-between mb-4">
                        <span style="font-size:1.1rem;font-weight:700;color:#fff">Total</span>
                        <span id="cartTotal" style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;color:#fff">
                            $<?= number_format($total * 1.13, 2) ?>
                        </span>
                    </div>
                    <button type="button" class="btn btn-accent w-100 py-3"
                            data-bs-toggle="modal" data-bs-target="#modalPago">
                        Confirmar Compra →
                    </button>
                    <p class="small text-center mt-3" style="color:#aaa">
                        🔒 Pago seguro y protegido
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL MÉTODO DE PAGO -->
<div class="modal fade" id="modalPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px">
        <div class="modal-content" style="background:#0f0f18;border:1px solid rgba(255,255,255,.08);border-radius:16px;">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.06);padding:1.25rem 1.5rem;">
                <div>
                    <h5 class="modal-title mb-0" style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:#fff;letter-spacing:1px;">
                        💳 Método de Pago
                    </h5>
                    <p class="mb-0 mt-1" style="font-size:.78rem;color:#888">Selecciona cómo deseas pagar</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <div class="d-flex flex-column gap-3 mb-4" id="metodosPago">
                    <!-- Efectivo -->
                    <label class="pago-opcion" data-metodo="Efectivo">
                        <input type="radio" name="metodo_radio" value="Efectivo" class="d-none">
                        <div class="pago-card">
                            <span class="pago-icon">💵</span>
                            <div>
                                <p class="pago-nombre">Efectivo</p>
                                <p class="pago-desc">Paga en efectivo al momento de la entrega</p>
                            </div>
                            <span class="pago-check">✓</span>
                        </div>
                    </label>
                    <!-- QR -->
                    <label class="pago-opcion" data-metodo="QR">
                        <input type="radio" name="metodo_radio" value="QR" class="d-none">
                        <div class="pago-card">
                            <span class="pago-icon">📱</span>
                            <div>
                                <p class="pago-nombre">Pago QR</p>
                                <p class="pago-desc">Escanea el QR con tu app bancaria</p>
                            </div>
                            <span class="pago-check">✓</span>
                        </div>
                    </label>
                    <!-- Tarjeta -->
                    <label class="pago-opcion" data-metodo="Tarjeta">
                        <input type="radio" name="metodo_radio" value="Tarjeta" class="d-none">
                        <div class="pago-card">
                            <span class="pago-icon">💳</span>
                            <div>
                                <p class="pago-nombre">Tarjeta Débito / Crédito</p>
                                <p class="pago-desc">Visa, Mastercard y otras tarjetas</p>
                            </div>
                            <span class="pago-check">✓</span>
                        </div>
                    </label>
                    <!-- Transferencia -->
                    <label class="pago-opcion" data-metodo="Transferencia">
                        <input type="radio" name="metodo_radio" value="Transferencia" class="d-none">
                        <div class="pago-card">
                            <span class="pago-icon">🏦</span>
                            <div>
                                <p class="pago-nombre">Transferencia Bancaria</p>
                                <p class="pago-desc">Transfiere desde tu banco directamente</p>
                            </div>
                            <span class="pago-check">✓</span>
                        </div>
                    </label>
                </div>

                <!-- Formulario oculto que se envía al confirmar -->
                <form method="POST" action="../../controllers/ProductoController.php" id="formPago">
                    <input type="hidden" name="accion" value="confirmar_compra">
                    <input type="hidden" name="metodo_pago" id="inputMetodoPago" value="">
                    <button type="submit" id="btnConfirmarPago"
                            class="btn btn-accent w-100 py-3" disabled
                            style="font-size:1rem;font-weight:600;opacity:.5;transition:opacity .2s">
                        Selecciona un método de pago
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.pago-opcion { cursor:pointer; }
.pago-card {
    display:flex; align-items:center; gap:1rem;
    background:#16161e; border:2px solid rgba(255,255,255,.06);
    border-radius:12px; padding:1rem 1.25rem;
    transition:border-color .18s, background .18s;
}
.pago-opcion:hover .pago-card { border-color:rgba(232,39,43,.35); background:#1a1a24; }
.pago-opcion.selected .pago-card { border-color:#e8272b; background:rgba(232,39,43,.08); }
.pago-icon { font-size:1.8rem; flex-shrink:0; }
.pago-nombre { font-weight:600; color:#fff; font-size:.95rem; margin:0; }
.pago-desc   { font-size:.78rem; color:#888; margin:0; }
.pago-check  {
    margin-left:auto; width:22px; height:22px; border-radius:50%;
    background:#e8272b; color:#fff; font-size:.75rem; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    opacity:0; transition:opacity .18s; flex-shrink:0;
}
.pago-opcion.selected .pago-check { opacity:1; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/toasts.js"></script>
<?php require_once '../../includes/flash_toast.php'; ?>
<script>
// ============================================
//  CARRITO — Gestión de Cantidades con AJAX
// ============================================
(function() {
    const CONTROLLER_URL = '../../controllers/ProductoController.php';
    const debounceTimers = {};

    // ---- Selección de método de pago ----
    document.querySelectorAll('.pago-opcion').forEach(function(opt) {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.pago-opcion').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            const metodo = this.dataset.metodo;
            document.getElementById('inputMetodoPago').value = metodo;
            const btn = document.getElementById('btnConfirmarPago');
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.textContent = 'Pagar con ' + metodo + ' →';
        });
    });

    // ---- Formatear moneda ----
    function fmt(v) {
        return '$' + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ---- Toast de notificación ----
    function showCartToast(msg, tipo) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        const bg = tipo === 'success' ? '#15803d' : tipo === 'warning' ? '#b45309' : '#e8272b';
        toast.style.cssText = `background:${bg};color:#fff;padding:14px 22px;border-radius:10px;font-size:.9rem;font-weight:500;max-width:360px;box-shadow:0 8px 24px rgba(0,0,0,.4);animation:slideIn .3s ease;`;
        toast.textContent = msg;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 3000);
    }

    // ---- Llamada AJAX para persistir cantidad ----
    function actualizarCantidadAJAX(idProducto, cantidad, row) {
        const input    = row.querySelector('.qty-input');
        const btnMinus = row.querySelector('[data-action="minus"]');
        const btnPlus  = row.querySelector('[data-action="plus"]');
        const subEl    = row.querySelector('.subtotal-val');

        // Feedback visual: loading
        input.style.opacity = '0.5';

        fetch(CONTROLLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=actualizar_cantidad&id_producto=${idProducto}&cantidad=${cantidad}`
        })
        .then(res => res.json())
        .then(data => {
            input.style.opacity = '1';

            if (!data.ok) {
                showCartToast(data.error, 'error');
                return;
            }

            // Actualizar input con valor clamped del servidor
            input.value = data.cantidad;
            input.dataset.stock = data.stock;

            // Actualizar botones +/−
            if (btnMinus) btnMinus.disabled = data.cantidad <= 1;
            if (btnPlus)  btnPlus.disabled  = data.cantidad >= data.stock;

            // Actualizar subtotal con animación
            subEl.textContent = fmt(data.subtotal);
            subEl.style.transition = 'color .3s';
            subEl.style.color = '#22c55e';
            setTimeout(() => { subEl.style.color = ''; }, 600);

            // Actualizar stock indicator
            const stockEl = row.querySelector('.stock-indicator');
            if (stockEl) {
                if (data.stock <= 3) {
                    stockEl.style.color = data.stock === 0 ? '#e8272b' : '#f59e0b';
                    stockEl.textContent = `⚠️ Solo ${data.stock} disponible${data.stock !== 1 ? 's' : ''}`;
                } else {
                    stockEl.style.color = '#666';
                    stockEl.textContent = `Stock: ${data.stock} unidades`;
                }
            }

            // Recalcular totales generales
            actualizarTotales();

            // Mostrar toast si fue ajustado
            if (data.clamped && data.mensaje) {
                showCartToast(data.mensaje, 'warning');
            }
        })
        .catch(() => {
            input.style.opacity = '1';
            showCartToast('Error de conexión. Intenta nuevamente.', 'error');
        });
    }

    // ---- Botones +/− ----
    document.querySelectorAll('.cart-item-row .qty-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (this.disabled) return;
            const row   = this.closest('tr');
            const input = row.querySelector('.qty-input');
            const stock = parseInt(input.dataset.stock) || 999;
            const idProd = row.dataset.id;
            let qty = parseInt(input.value) || 1;

            if (this.dataset.action === 'plus'  && qty < stock) qty++;
            if (this.dataset.action === 'minus' && qty > 1)     qty--;

            input.value = qty;

            // Actualizar estado visual de botones inmediatamente
            const btnMinus = row.querySelector('[data-action="minus"]');
            const btnPlus  = row.querySelector('[data-action="plus"]');
            if (btnMinus) btnMinus.disabled = qty <= 1;
            if (btnPlus)  btnPlus.disabled  = qty >= stock;

            // Actualizar subtotal optimísticamente
            const price = parseFloat(row.querySelector('[data-price]').dataset.price);
            row.querySelector('.subtotal-val').textContent = fmt(price * qty);
            actualizarTotales();

            // Persistir en servidor
            actualizarCantidadAJAX(idProd, qty, row);
        });
    });

    // ---- Edición manual del input (con debounce) ----
    document.querySelectorAll('.cart-item-row .qty-input').forEach(function(input) {
        // Al escribir: debounce de 500ms
        input.addEventListener('input', function() {
            const row    = this.closest('tr');
            const idProd = row.dataset.id;

            clearTimeout(debounceTimers[idProd]);
            debounceTimers[idProd] = setTimeout(() => {
                let qty = parseInt(this.value);
                if (isNaN(qty) || qty < 1) qty = 1;
                this.value = qty;

                // Actualizar subtotal optimísticamente
                const price = parseFloat(row.querySelector('[data-price]').dataset.price);
                row.querySelector('.subtotal-val').textContent = fmt(price * qty);
                actualizarTotales();

                // Persistir en servidor (validará stock real)
                actualizarCantidadAJAX(idProd, qty, row);
            }, 500);
        });

        // Al perder foco: enviar inmediatamente
        input.addEventListener('blur', function() {
            const row    = this.closest('tr');
            const idProd = row.dataset.id;
            clearTimeout(debounceTimers[idProd]);

            let qty = parseInt(this.value);
            if (isNaN(qty) || qty < 1) {
                qty = 1;
                this.value = 1;
            }

            actualizarCantidadAJAX(idProd, qty, row);
        });

        // Seleccionar todo al hacer focus para fácil edición
        input.addEventListener('focus', function() {
            this.select();
        });

        // Enter para confirmar
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });

    // ---- Recalcular Subtotal + IVA + Total ----
    function actualizarTotales() {
        let sub = 0;
        document.querySelectorAll('.subtotal-val').forEach(function(el) {
            sub += parseFloat(el.textContent.replace(/[$,]/g, '')) || 0;
        });
        const iva   = sub * 0.13;
        const total = sub + iva;
        const subs  = document.querySelectorAll('.d-flex.justify-content-between span.fw-600');
        if (subs[0]) subs[0].textContent = fmt(sub);
        if (subs[1]) subs[1].textContent = fmt(iva);
        const cartTotal = document.getElementById('cartTotal');
        if (cartTotal) cartTotal.textContent = fmt(total);
    }
})();
</script>

<!-- MODAL CONFIRMACIÓN ELIMINAR -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="background:#0f0f18;border:1px solid rgba(255,255,255,.08);border-radius:16px;">
            <div class="modal-body" style="padding:2rem;text-align:center;">
                <div class="confirm-icon" id="confirmIcon">🗑️</div>
                <h5 class="confirm-title" id="confirmTitle">¿Eliminar producto?</h5>
                <p class="confirm-desc" id="confirmDesc">Se eliminará del carrito.</p>
                <div class="d-flex gap-3 mt-4">
                    <button type="button" class="btn confirm-btn-cancel flex-fill" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <a href="#" class="btn confirm-btn-action flex-fill" id="confirmActionBtn">
                        Eliminar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal de confirmación */
.confirm-icon {
    font-size: 3rem;
    margin-bottom: 12px;
    animation: confirmBounce .5s ease;
}
@keyframes confirmBounce {
    0%   { transform: scale(0.3); opacity: 0; }
    50%  { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}
.confirm-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.5rem;
    color: #fff;
    letter-spacing: 1px;
    margin-bottom: 8px;
}
.confirm-desc {
    color: #888899;
    font-size: 0.88rem;
    line-height: 1.5;
    margin-bottom: 0;
}
.confirm-btn-cancel {
    background: #16161e;
    border: 1px solid rgba(255,255,255,0.1);
    color: #bbb;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all .2s;
}
.confirm-btn-cancel:hover {
    background: #1c1c28;
    color: #fff;
    border-color: rgba(255,255,255,0.2);
}
.confirm-btn-action {
    background: linear-gradient(135deg, #e8272b, #c41e22);
    border: none;
    color: #fff;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .2s;
}
.confirm-btn-action:hover {
    background: linear-gradient(135deg, #ff3338, #e8272b);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(232,39,43,0.35);
}
</style>

<script>
// ---- MODAL DE CONFIRMACIÓN ----
(function() {
    const modalEl    = document.getElementById('modalConfirmarEliminar');
    if (!modalEl) return;
    const bsModal    = new bootstrap.Modal(modalEl);
    const iconEl     = document.getElementById('confirmIcon');
    const titleEl    = document.getElementById('confirmTitle');
    const descEl     = document.getElementById('confirmDesc');
    const actionBtn  = document.getElementById('confirmActionBtn');

    // Eliminar producto individual
    document.querySelectorAll('.btn-confirmar-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const nombre = this.dataset.nombre;
            const url    = this.dataset.url;
            iconEl.textContent   = '🗑️';
            titleEl.textContent  = '¿Eliminar producto?';
            descEl.innerHTML     = 'Se eliminará <strong style="color:#f0f0f0">' + nombre + '</strong> de tu carrito.';
            actionBtn.textContent = 'Sí, eliminar';
            actionBtn.href       = url;
            bsModal.show();
        });
    });

    // Vaciar carrito completo
    const btnVaciar = document.querySelector('.btn-confirmar-vaciar');
    if (btnVaciar) {
        btnVaciar.addEventListener('click', function() {
            iconEl.textContent   = '⚠️';
            titleEl.textContent  = '¿Vaciar el carrito?';
            descEl.textContent   = 'Se eliminarán todos los productos. Esta acción no se puede deshacer.';
            actionBtn.textContent = 'Sí, vaciar todo';
            actionBtn.href       = 'carrito.php?accion=vaciar';
            bsModal.show();
        });
    }
})();
</script>
</body>
</html>
