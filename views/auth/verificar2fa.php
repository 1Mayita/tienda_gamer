<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA — AutoZone</title>
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

// Si no hay sesión pre-2FA, redirigir al login
if (!isset($_SESSION['pre_2fa'])) {
    header('Location: login.php');
    exit;
}
$pre  = $_SESSION['pre_2fa'];
$flash = getFlash();
?>

<div class="auth-container" style="max-width:500px; margin:0 auto; min-height:100vh; display:flex; align-items:center;">
    <div class="auth-right w-100">
        <div class="auth-form-wrap">
            <div class="text-center mb-5">
                <div class="twofa-icon">🔐</div>
                <h1 class="auth-title mt-3">Verificación 2FA</h1>
                <p class="auth-desc">
                    Hola, <strong><?= htmlspecialchars($pre['nombre']) ?></strong>.<br>
                    Ingresa el código de 6 dígitos para acceder.
                </p>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mb-4">
                <?= htmlspecialchars($flash['mensaje']) ?>
            </div>
            <?php endif; ?>

            <!-- Código 2FA visible (simulación) -->
            <div class="code-demo-box mb-4">
                <p class="code-demo-label">📧 Código enviado a tu correo:</p>
                <div class="code-demo-value"><?= htmlspecialchars($pre['codigo_2fa']) ?></div>
                <p class="code-demo-hint">En producción este código se envía por email o SMS</p>
            </div>

            <form method="POST" action="../../controllers/AuthController.php">
                <input type="hidden" name="accion" value="verificar2fa">

                <div class="auth-field mb-4">
                    <label class="auth-label">Código de Verificación</label>
                    <input type="text"
                           name="codigo_2fa"
                           class="auth-input text-center"
                           placeholder="000000"
                           maxlength="6"
                           pattern="\d{6}"
                           inputmode="numeric"
                           autocomplete="one-time-code"
                           required
                           autofocus
                           style="font-size:1.8rem;letter-spacing:8px;font-weight:700;">
                </div>

                <div class="text-center mb-4">
                    <span class="code-timer">
                        ⏱ Expira en: <strong id="countdown2fa" data-secs="120">2:00</strong>
                    </span>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-3 auth-submit-btn">
                    Verificar y Acceder →
                </button>
            </form>

            <p class="auth-switch mt-4 text-center">
                <a href="login.php" class="auth-link">← Volver al Login</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
