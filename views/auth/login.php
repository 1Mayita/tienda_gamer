<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — AutoZone</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/landing.css">
    <link rel="stylesheet" href="../../assets/css/auth.css">
</head>
<body class="auth-body">

<?php
require_once '../../config/database.php';
require_once '../../includes/funciones.php';
iniciarSesion();
if (estaLogueado()) {
    header('Location: ' . BASE_URL . (esAdmin() ? 'views/admin/dashboard.php' : 'views/client/dashboard.php'));
    exit;
}
$flash = getFlash();
?>

<div class="auth-container">
    <!-- Panel Izquierdo -->
    <div class="auth-left d-none d-lg-flex">
        <div class="auth-left-content">
            <a href="../../index.php" class="brand-logo mb-5 d-block">
                <span class="brand-icon">⬡</span> AUTO<span class="brand-accent">ZONE</span>
            </a>
            <h2 class="auth-tagline">Bienvenido de<br><span class="brand-accent">vuelta</span></h2>
            <p class="auth-desc">Accede a tu cuenta para explorar nuestro catálogo exclusivo de vehículos premium.</p>
            <div class="auth-features mt-4">
                <div class="auth-feature-item">✓ Catálogo de más de 200 vehículos</div>
                <div class="auth-feature-item">✓ Seguimiento de pedidos en tiempo real</div>
                <div class="auth-feature-item">✓ Lista de favoritos personalizada</div>
                <div class="auth-feature-item">✓ Autenticación segura con 2FA</div>
            </div>
        </div>
        <div class="auth-left-bg"></div>
    </div>

    <!-- Panel Derecho -->
    <div class="auth-right">
        <div class="auth-form-wrap">
            <div class="d-flex align-items-center justify-content-between mb-5">
                <h1 class="auth-title">Iniciar Sesión</h1>
                <a href="../../index.php" class="auth-back">← Inicio</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mb-4">
                <?= htmlspecialchars($flash['mensaje']) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="../../controllers/AuthController.php" id="authForm" novalidate>
                <input type="hidden" name="accion" value="login">

                <div class="auth-field mb-4">
                    <label class="auth-label">Correo electrónico</label>
                    <input type="email" name="correo" class="auth-input" placeholder="tu@correo.com" required autofocus>
                </div>

                <div class="auth-field mb-4">
                    <label class="auth-label">Contraseña</label>
                    <div class="position-relative">
                        <input type="password" name="contrasena" id="contrasena" class="auth-input" placeholder="••••••••" required>
                        <button type="button" class="toggle-pass auth-eye" data-target="#contrasena">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-3 auth-submit-btn">
                    Ingresar →
                </button>
            </form>

            <p class="auth-switch mt-4 text-center">
                ¿No tienes cuenta?
                <a href="registro.php" class="auth-link">Regístrate gratis</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
