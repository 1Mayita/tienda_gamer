<?php
require_once 'config/database.php';
$db = getDB();

// Categorías con sus marcas disponibles
$rows = $db->query('
    SELECT c.id_categoria, c.nombre_categoria, p.marca
    FROM Categoria c
    LEFT JOIN Producto p ON c.id_categoria = p.id_categoria AND p.estado = 1
    GROUP BY c.id_categoria, p.marca
    ORDER BY c.nombre_categoria, p.marca
')->fetchAll();

$categoriasMenu = [];
foreach ($rows as $r) {
    $id = $r['id_categoria'];
    if (!isset($categoriasMenu[$id])) {
        $categoriasMenu[$id] = ['id' => $id, 'nombre' => $r['nombre_categoria'], 'marcas' => []];
    }
    if ($r['marca']) {
        $categoriasMenu[$id]['marcas'][] = $r['marca'];
    }
}

$iconosCat = [1 => '🚗', 2 => '🚙', 3 => '🏎️', 4 => '🛻', 5 => '⚡'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoZone — Vehículos de Alto Rendimiento</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
    <style>
        /* ── Mega-menú de categorías ─────────────────── */
        .mega-dropdown { position: static !important; }
        .mega-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            width: min(860px, 96vw);
            background: #0f0f18;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,.7);
            padding: 1.5rem;
            z-index: 9999;
            display: none;
            animation: fadeDown .18s ease;
        }
        @keyframes fadeDown {
            from { opacity:0; transform:translateX(-50%) translateY(-8px); }
            to   { opacity:1; transform:translateX(-50%) translateY(0); }
        }
        .mega-menu.show { display: block; }
        .mega-col-title {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1rem;
            letter-spacing: 1px;
            color: #fff;
            padding: .4rem .6rem;
            border-radius: 6px;
            text-decoration: none;
            transition: background .15s;
            margin-bottom: .5rem;
        }
        .mega-col-title:hover { background: rgba(232,39,43,.12); color: #e8272b; }
        .mega-col-title .cat-emoji { font-size: 1.1rem; }
        .mega-brand-link {
            display: block;
            font-size: .82rem;
            color: #888;
            padding: .3rem .6rem .3rem 1.8rem;
            border-radius: 5px;
            text-decoration: none;
            transition: color .15s, background .15s;
            white-space: nowrap;
        }
        .mega-brand-link:hover { color: #fff; background: rgba(255,255,255,.05); }
        .mega-brand-link::before { content: '→ '; opacity: .4; }
        .mega-divider-v {
            width: 1px;
            background: rgba(255,255,255,.06);
            margin: 0 .5rem;
        }
        .mega-footer {
            border-top: 1px solid rgba(255,255,255,.06);
            margin-top: 1rem;
            padding-top: 1rem;
        }
        .mega-ver-todo {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: .85rem;
            font-weight: 600;
            color: #e8272b;
            text-decoration: none;
            padding: .4rem .8rem;
            border: 1px solid rgba(232,39,43,.3);
            border-radius: 20px;
            transition: background .15s;
        }
        .mega-ver-todo:hover { background: rgba(232,39,43,.1); color: #e8272b; }

        /* Botón Categorías en navbar */
        .nav-cat-btn {
            display: flex;
            align-items: center;
            gap: .35rem;
            background: none;
            border: none;
            color: rgba(255,255,255,.75);
            font-size: .9rem;
            padding: .5rem .75rem;
            cursor: pointer;
            transition: color .15s;
            font-family: 'Inter', sans-serif;
        }
        .nav-cat-btn:hover, .nav-cat-btn.active { color: #fff; }
        .nav-cat-btn .chevron {
            font-size: .65rem;
            transition: transform .2s;
            display: inline-block;
        }
        .nav-cat-btn.active .chevron { transform: rotate(180deg); }

        /* Posición relativa en el navbar */
        #navMenu { position: static; }
        .navbar { position: fixed; top:0; left:0; right:0; z-index:1000; }
        .mega-wrapper { position: static; }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container position-relative">
        <a class="navbar-brand brand-logo" href="index.php">
            <span class="brand-icon">⬡</span> AUTO<span class="brand-accent">ZONE</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse position-static" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link nav-scroll" href="#catalogo">Catálogo</a></li>

                <!-- ── CATEGORÍAS MEGA-MENÚ ── -->
                <li class="nav-item mega-wrapper">
                    <button class="nav-cat-btn" id="catBtn" onclick="toggleMega(event)" type="button">
                        Categorías <span class="chevron">▼</span>
                    </button>
                    <div class="mega-menu" id="megaMenu">
                        <div class="d-flex gap-0 flex-wrap">
                            <?php $first = true; foreach ($categoriasMenu as $cat): ?>
                            <?php if (!$first): ?><div class="mega-divider-v d-none d-lg-block"></div><?php endif; $first = false; ?>
                            <div class="flex-fill px-2" style="min-width:130px">
                                <!-- Título de categoría → va al catálogo filtrado -->
                                <a href="views/client/catalogo.php?categoria=<?= $cat['id'] ?>"
                                   class="mega-col-title">
                                    <span class="cat-emoji"><?= $iconosCat[$cat['id']] ?? '🚘' ?></span>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </a>
                                <!-- Marcas / sub-items -->
                                <?php foreach ($cat['marcas'] as $marca): ?>
                                <a href="views/client/catalogo.php?categoria=<?= $cat['id'] ?>&marca=<?= urlencode($marca) ?>"
                                   class="mega-brand-link">
                                    <?= htmlspecialchars($marca) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Footer del mega-menú -->
                        <div class="mega-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="text-muted" style="font-size:.78rem">
                                Selecciona una categoría o marca para explorar
                            </span>
                            <a href="views/client/catalogo.php" class="mega-ver-todo">
                                🔍 Ver todo el catálogo →
                            </a>
                        </div>
                    </div>
                </li>
                <!-- ── FIN MEGA-MENÚ ── -->

                <li class="nav-item"><a class="nav-link nav-scroll" href="#beneficios">Beneficios</a></li>
                <li class="nav-item"><a class="nav-link nav-scroll" href="#contacto">Contacto</a></li>
                <li class="nav-item ms-2">
                    <a class="btn btn-outline-light btn-sm px-4" href="views/auth/login.php">Ingresar</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-accent btn-sm px-4" href="views/auth/registro.php">Registrarse</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ========== HERO ========== -->
<section class="hero-section" id="inicio">
    <div class="hero-overlay"></div>
    <div class="hero-grid-lines"></div>
    <div class="container position-relative">
        <div class="hero-content">
            <span class="hero-badge"> #1 en Bolivia</span>
            <h1 class="hero-title">
                CONDUCE EL<br>
                <span class="hero-accent">FUTURO</span><br>
                HOY
            </h1>
            <p class="hero-subtitle">
                Los vehículos más exclusivos del mundo,<br>ahora en tu ciudad. Financiamiento inmediato.
            </p>
            <div class="hero-cta d-flex gap-3 flex-wrap">
                <a href="#catalogo" class="btn btn-accent btn-lg px-5">Ver Catálogo</a>
                <a href="views/auth/registro.php" class="btn btn-outline-light btn-lg px-5">Crear Cuenta</a>
            </div>
            <div class="hero-stats d-flex gap-5 mt-5">
                <div class="hero-stat">
                    <span class="stat-num">200+</span>
                    <span class="stat-label">Vehículos</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-num">15+</span>
                    <span class="stat-label">Marcas</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-num">5K+</span>
                    <span class="stat-label">Clientes</span>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-scroll-hint">
        <span>Scroll</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- ========== CATÁLOGO DESTACADO ========== -->
<section class="py-5 section-light" id="catalogo">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="section-badge">DESTACADOS</span>
            <h2 class="section-title">Vehículos Premium</h2>
            <p class="section-subtitle">Selección exclusiva de los mejores modelos disponibles</p>
        </div>

        <!-- Buscador -->
        <div class="search-bar mb-5">
            <input type="text" id="buscador" class="search-input" placeholder="Buscar por nombre, marca o categoría...">
            <button class="search-btn">🔍</button>
        </div>

        <div class="row g-4" id="productosGrid">
            <?php
            $stmt = $db->query('
                SELECT p.*, c.nombre_categoria
                FROM Producto p
                JOIN Categoria c ON p.id_categoria = c.id_categoria
                WHERE p.estado = 1
                LIMIT 8
            ');
            $productos = $stmt->fetchAll();
            foreach ($productos as $p): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 producto-item"
                 data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>"
                 data-marca="<?= strtolower(htmlspecialchars($p['marca'])) ?>"
                 data-cat="<?= strtolower(htmlspecialchars($p['nombre_categoria'])) ?>">
                <div class="product-card">
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
                        <p class="product-desc"><?= htmlspecialchars(substr($p['descripcion'], 0, 80)) ?>...</p>
                        <div class="product-footer">
                            <span class="product-price">$<?= number_format($p['precio'], 0, '.', ',') ?></span>
                            <div class="product-actions">
                                <a href="views/auth/login.php" class="btn-action btn-fav" title="Favorito">♡</a>
                                <a href="views/auth/login.php" class="btn-action btn-cart" title="Al carrito">🛒</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="views/auth/login.php" class="btn btn-accent btn-lg px-5">Ver Catálogo Completo</a>
        </div>
    </div>
</section>

<!-- ========== BENEFICIOS ========== -->
<section class="py-5 section-dark" id="beneficios">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="section-badge">¿POR QUÉ NOSOTROS?</span>
            <h2 class="section-title">La Experiencia AutoZone</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="benefit-card">
                    <div class="benefit-icon">🛡️</div>
                    <h4 class="benefit-title">Garantía Total</h4>
                    <p class="benefit-desc">Todos nuestros vehículos incluyen garantía de fábrica y revisión técnica certificada.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="benefit-card">
                    <div class="benefit-icon">💳</div>
                    <h4 class="benefit-title">Financiamiento</h4>
                    <p class="benefit-desc">Aprobación inmediata. Cuotas accesibles adaptadas a tu presupuesto sin letra pequeña.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="benefit-card">
                    <div class="benefit-icon">🚚</div>
                    <h4 class="benefit-title">Entrega a Domicilio</h4>
                    <p class="benefit-desc">Llevamos tu vehículo hasta la puerta de tu casa. Cobertura nacional en 24–72h.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="benefit-card">
                    <div class="benefit-icon">🔧</div>
                    <h4 class="benefit-title">Soporte 24/7</h4>
                    <p class="benefit-desc">Asistencia en carretera y soporte técnico disponible todo el año, los 365 días.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== BANNER CTA ========== -->
<section class="cta-section">
    <div class="cta-overlay"></div>
    <div class="container position-relative text-center">
        <h2 class="cta-title">¿Listo para tu próximo vehículo?</h2>
        <p class="cta-sub">Regístrate gratis y accede a precios exclusivos, favoritos y seguimiento de pedidos.</p>
        <a href="views/auth/registro.php" class="btn btn-accent btn-lg px-5 me-3">Crear Cuenta Gratis</a>
        <a href="#contacto" class="btn btn-outline-light btn-lg px-5">Contactar Asesor</a>
    </div>
</section>

<!-- ========== CONTACTO ========== -->
<section class="py-5 section-dark" id="contacto">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Información + botón -->
            <div class="col-lg-7">
                <span class="section-badge">CONTÁCTANOS</span>
                <h2 class="section-title mt-2">Estamos Aquí Para Ayudarte</h2>
                <p class="section-subtitle">Nuestros asesores están listos para guiarte en cada paso del proceso.</p>
                <div class="contact-info d-flex flex-column gap-3 mt-4 mb-5">
                    <div class="contact-item"><span>📍</span> Av. Principal 123, La Paz, Bolivia</div>
                    <div class="contact-item"><span>📞</span> +591 2 123-4567</div>
                    <div class="contact-item"><span>📧</span> info@autozone.com</div>
                    <div class="contact-item"><span>🕐</span> Lun–Sáb: 9:00 – 19:00</div>
                </div>
                <button class="btn btn-accent px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalContacto">
                    Enviar mensaje →
                </button>
            </div>
            <!-- Tarjeta visual derecha -->
            <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                <div style="background:rgba(232,39,43,.06);border:1px solid rgba(232,39,43,.18);border-radius:16px;padding:2.5rem;text-align:center;max-width:280px;width:100%;">
                    <div style="font-size:3rem;margin-bottom:1rem;">💬</div>
                    <p style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:#fff;letter-spacing:1px;margin-bottom:.5rem;">¿Tienes dudas?</p>
                    <p style="font-size:.85rem;color:#888;margin-bottom:1.5rem;">Respuesta garantizada en menos de 24 horas hábiles.</p>
                    <button class="btn btn-outline-light btn-sm px-4" data-bs-toggle="modal" data-bs-target="#modalContacto">
                        Contactar asesor
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== MODAL CONTACTO ========== -->
<div class="modal fade" id="modalContacto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
        <div class="modal-content" style="background:#0f0f18;border:1px solid rgba(255,255,255,.08);border-radius:16px;">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.06);padding:1.25rem 1.5rem;">
                <div>
                    <h5 class="modal-title mb-0" style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:#fff;letter-spacing:1px;">
                        📩 Envíanos un Mensaje
                    </h5>
                    <p class="mb-0 mt-1" style="font-size:.78rem;color:#666;">Te respondemos en menos de 24 horas</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <!-- Confirmación de éxito -->
                <div id="contactSuccess" class="d-none text-center py-4">
                    <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
                    <h5 style="color:#fff;font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1px;">¡Mensaje Enviado!</h5>
                    <p style="color:#888;font-size:.9rem;">Nos pondremos en contacto contigo pronto.</p>
                    <button class="btn btn-outline-light btn-sm mt-2" data-bs-dismiss="modal">Cerrar</button>
                </div>
                <!-- Formulario -->
                <form id="contactForm" class="row g-3">
                    <div class="col-md-6">
                        <label class="auth-label">Tu nombre *</label>
                        <input type="text" class="auth-input" id="ctNombre" placeholder="Juan Pérez" required>
                    </div>
                    <div class="col-md-6">
                        <label class="auth-label">Tu correo *</label>
                        <input type="email" class="auth-input" id="ctCorreo" placeholder="tu@correo.com" required>
                    </div>
                    <div class="col-12">
                        <label class="auth-label">Teléfono <span style="color:#666;font-weight:400">(opcional)</span></label>
                        <input type="tel" class="auth-input" id="ctTelefono" placeholder="+591 7 000-0000">
                    </div>
                    <div class="col-12">
                        <label class="auth-label">Asunto *</label>
                        <select class="auth-input" id="ctAsunto" required>
                            <option value="">Seleccionar...</option>
                            <option>Consulta sobre un vehículo</option>
                            <option>Información de financiamiento</option>
                            <option>Agendar prueba de manejo</option>
                            <option>Soporte postventa</option>
                            <option>Otro</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="auth-label">Mensaje *</label>
                        <textarea class="auth-input" id="ctMensaje" rows="4"
                                  placeholder="Cuéntanos en qué podemos ayudarte..." required
                                  style="resize:vertical;min-height:100px;"></textarea>
                    </div>
                    <div class="col-12 pt-1">
                        <button type="submit" class="btn btn-accent w-100 py-2">
                            Enviar Mensaje →
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="footer-brand">⬡ AUTO<span class="brand-accent">ZONE</span></div>
                <p class="footer-desc">Líderes en venta de vehículos premium en Bolivia. Tu confianza es nuestro motor.</p>
                <div class="social-links d-flex gap-3 mt-3">
                    <a href="#" class="social-link">f</a>
                    <a href="#" class="social-link">in</a>
                    <a href="#" class="social-link">ig</a>
                    <a href="#" class="social-link">yt</a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="footer-heading">Catálogo</h6>
                <ul class="footer-links">
                    <?php foreach ($categoriasMenu as $cat): ?>
                    <li>
                        <a href="views/client/catalogo.php?categoria=<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="footer-heading">Empresa</h6>
                <ul class="footer-links">
                    <li><a href="#">Sobre Nosotros</a></li>
                    <li><a href="#">Trabaja con Nosotros</a></li>
                    <li><a href="#">Prensa</a></li>
                    <li><a href="#">Sostenibilidad</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-4">
                <h6 class="footer-heading">Newsletter</h6>
                <p class="footer-desc">Recibe las mejores ofertas en tu correo.</p>
                <div class="newsletter-form d-flex gap-2 mt-3">
                    <input type="email" class="form-control form-dark" placeholder="tu@correo.com">
                    <button class="btn btn-accent px-3">→</button>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <p class="footer-copy">© 2025 AutoZone. Todos los derechos reservados.</p>
            <div class="d-flex gap-4">
                <a href="#" class="footer-link-sm">Privacidad</a>
                <a href="#" class="footer-link-sm">Términos</a>
                <a href="#" class="footer-link-sm">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// ── Modal de contacto ──────────────────────────────────
document.getElementById('contactForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // Validación básica
    const nombre  = document.getElementById('ctNombre').value.trim();
    const correo  = document.getElementById('ctCorreo').value.trim();
    const asunto  = document.getElementById('ctAsunto').value;
    const mensaje = document.getElementById('ctMensaje').value.trim();
    if (!nombre || !correo || !asunto || !mensaje) return;

    // Simular envío → mostrar confirmación
    this.classList.add('d-none');
    document.getElementById('contactSuccess').classList.remove('d-none');
});

// Resetear form al cerrar el modal
document.getElementById('modalContacto')?.addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('contactForm');
    const ok   = document.getElementById('contactSuccess');
    form?.reset();
    form?.classList.remove('d-none');
    ok?.classList.add('d-none');
});
</script>
<script>
// ── Mega-menú de categorías ────────────────────────────
function toggleMega(e) {
    e.stopPropagation();
    const btn  = document.getElementById('catBtn');
    const menu = document.getElementById('megaMenu');
    const open = menu.classList.contains('show');
    closeMega();
    if (!open) {
        menu.classList.add('show');
        btn.classList.add('active');
    }
}

function closeMega() {
    document.getElementById('megaMenu')?.classList.remove('show');
    document.getElementById('catBtn')?.classList.remove('active');
}

// Cerrar al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.mega-wrapper')) closeMega();
});

// Cerrar al presionar Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMega();
});
</script>
</body>
</html>
