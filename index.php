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
    <!-- prueba -->
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand brand-logo" href="index.php">
            <span class="brand-icon">⬡</span> AUTO<span class="brand-accent">ZONE</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link nav-scroll" href="#catalogo">Catálogo</a></li>
                <li class="nav-item"><a class="nav-link nav-scroll" href="#categorias">Categorías</a></li>
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
            <span class="hero-badge">🏆 #1 en Bolivia</span>
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

<!-- ========== CATEGORÍAS ========== -->
<section class="py-5 section-dark" id="categorias">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="section-badge">EXPLORAR</span>
            <h2 class="section-title">Nuestras Categorías</h2>
            <p class="section-subtitle">Encuentra el vehículo que se adapta a tu estilo de vida</p>
        </div>
        <div class="row g-4">
            <?php
            require_once 'config/database.php';
            $db   = getDB();
            $cats = $db->query('SELECT * FROM Categoria LIMIT 5')->fetchAll();
            $iconos = ['🚗','🚙','🏎️','🛻','⚡'];
            $colores = ['cat-red','cat-blue','cat-gold','cat-dark','cat-green'];
            foreach ($cats as $i => $cat): ?>
            <div class="col-lg-2 col-md-4 col-6">
                <a href="views/client/catalogo.php?categoria=<?= $cat['id_categoria'] ?>" class="cat-card <?= $colores[$i % 5] ?>">
                    <span class="cat-icon"><?= $iconos[$i % 5] ?></span>
                    <span class="cat-name"><?= htmlspecialchars($cat['nombre_categoria']) ?></span>
                    <span class="cat-arrow">→</span>
                </a>
            </div>
            <?php endforeach; ?>
            <div class="col-lg-2 col-md-4 col-6">
                <a href="views/client/catalogo.php" class="cat-card cat-all">
                    <span class="cat-icon">🔍</span>
                    <span class="cat-name">Ver Todo</span>
                    <span class="cat-arrow">→</span>
                </a>
            </div>
        </div>
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
                        <img src="assets/img/<?= htmlspecialchars($p['imagen']) ?>"
                             onerror="this.src='assets/img/default.jpg'"
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
                            <span class="product-price">
                                $<?= number_format($p['precio'], 0, '.', ',') ?>
                            </span>
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
            <div class="col-lg-5">
                <span class="section-badge">CONTÁCTANOS</span>
                <h2 class="section-title mt-2">Estamos Aquí Para Ayudarte</h2>
                <p class="section-subtitle">Nuestros asesores están listos para guiarte en cada paso.</p>
                <div class="contact-info d-flex flex-column gap-3 mt-4">
                    <div class="contact-item"><span>📍</span> Av. Principal 123, La Paz, Bolivia</div>
                    <div class="contact-item"><span>📞</span> +591 2 123-4567</div>
                    <div class="contact-item"><span>📧</span> info@autozone.com</div>
                    <div class="contact-item"><span>🕐</span> Lun–Sáb: 9:00 – 19:00</div>
                </div>
            </div>
            <div class="col-lg-7">
                <form class="contact-form" id="contactForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control form-dark" placeholder="Tu nombre" required>
                        </div>
                        <div class="col-md-6">
                            <input type="email" class="form-control form-dark" placeholder="Tu correo" required>
                        </div>
                        <div class="col-12">
                            <input type="text" class="form-control form-dark" placeholder="Asunto">
                        </div>
                        <div class="col-12">
                            <textarea class="form-control form-dark" rows="4" placeholder="Tu mensaje..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-accent w-100 py-3">Enviar Mensaje →</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

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
                    <li><a href="views/auth/login.php">Sedanes</a></li>
                    <li><a href="views/auth/login.php">SUVs</a></li>
                    <li><a href="views/auth/login.php">Deportivos</a></li>
                    <li><a href="views/auth/login.php">Camionetas</a></li>
                    <li><a href="views/auth/login.php">Eléctricos</a></li>
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
</body>
</html>
