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
require_once '../../config/mail.php';
require_once '../../includes/funciones.php';
iniciarSesion();

// Si no hay sesión pre-2FA, redirigir al login
if (!isset($_SESSION['pre_2fa'])) {
    header('Location: login.php');
    exit;
}
$pre   = $_SESSION['pre_2fa'];
$flash = getFlash();

// Calcular segundos restantes para expiración
$secsRestantes = max(0, ($pre['otp_timestamp'] + OTP_EXPIRACION_MINUTOS * 60) - time());

// Calcular cooldown del reenvío
$secsCooldown = max(0, ($pre['ultimo_reenvio'] + OTP_REENVIO_COOLDOWN) - time());

// Correo enmascarado
$correoEnmascarado = enmascararCorreo($pre['correo']);
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
            <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : ($flash['tipo'] === 'warning' ? 'warning' : 'danger') ?> flash-alert mb-4">
                <?= htmlspecialchars($flash['mensaje']) ?>
            </div>
            <?php endif; ?>

            <!-- Notificación de envío por correo -->
            <div class="otp-email-notice mb-4">
                <div class="otp-notice-icon">📧</div>
                <p class="otp-notice-text">
                    Se envió un código de verificación a<br>
                    <strong class="otp-notice-email"><?= htmlspecialchars($correoEnmascarado) ?></strong>
                </p>
                <p class="otp-notice-hint">Revisa tu bandeja de entrada y la carpeta de spam</p>
            </div>

            <form method="POST" action="../../controllers/AuthController.php" id="form2fa">
                <input type="hidden" name="accion" value="verificar2fa">

                <div class="auth-field mb-4">
                    <label class="auth-label">Código de Verificación</label>
                    <div class="otp-inputs-wrap" id="otpInputsWrap">
                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="\d" data-idx="0" autofocus>
                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="\d" data-idx="1">
                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="\d" data-idx="2">
                        <span class="otp-separator">—</span>
                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="\d" data-idx="3">
                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="\d" data-idx="4">
                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="\d" data-idx="5">
                    </div>
                    <!-- Campo oculto que se llena con JS -->
                    <input type="hidden" name="codigo_2fa" id="codigoOtpHidden">
                </div>

                <div class="text-center mb-4">
                    <span class="code-timer">
                        ⏱ Expira en: <strong id="countdown2fa" data-secs="<?= $secsRestantes ?>"><?= floor($secsRestantes / 60) . ':' . str_pad($secsRestantes % 60, 2, '0', STR_PAD_LEFT) ?></strong>
                    </span>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-3 auth-submit-btn" id="btnVerificar">
                    Verificar y Acceder →
                </button>
            </form>

            <!-- Botón de reenvío -->
            <div class="text-center mt-4">
                <form method="POST" action="../../controllers/AuthController.php" class="d-inline">
                    <input type="hidden" name="accion" value="reenviar2fa">
                    <button type="submit" class="btn-reenviar" id="btnReenviar" <?= $secsCooldown > 0 ? 'disabled' : '' ?>>
                        🔄 <span id="reenviarTexto"><?= $secsCooldown > 0 ? "Reenviar en {$secsCooldown}s" : '¿No recibiste el código? Reenviar' ?></span>
                    </button>
                </form>
            </div>

            <p class="auth-switch mt-3 text-center">
                <a href="login.php" class="auth-link">← Volver al Login</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
// ---- OTP INPUTS: Manejo individual por dígito ----
(function() {
    const wrap   = document.getElementById('otpInputsWrap');
    const inputs = wrap.querySelectorAll('.otp-digit');
    const hidden = document.getElementById('codigoOtpHidden');
    const form   = document.getElementById('form2fa');

    function actualizarHidden() {
        let val = '';
        inputs.forEach(inp => val += inp.value);
        hidden.value = val;
    }

    inputs.forEach((inp, idx) => {
        inp.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 1);
            actualizarHidden();
            if (this.value && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
            }
            // Auto-submit cuando se llenan los 6 dígitos
            if (hidden.value.length === 6) {
                form.submit();
            }
        });

        inp.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                inputs[idx - 1].focus();
                inputs[idx - 1].value = '';
                actualizarHidden();
            }
        });

        // Soporte para pegar código completo
        inp.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            for (let i = 0; i < pasted.length && i < inputs.length; i++) {
                inputs[i].value = pasted[i];
            }
            actualizarHidden();
            if (pasted.length >= 6) {
                inputs[5].focus();
                form.submit();
            } else if (pasted.length > 0) {
                inputs[Math.min(pasted.length, 5)].focus();
            }
        });
    });

    // ---- COOLDOWN REENVÍO ----
    const btnReenviar  = document.getElementById('btnReenviar');
    const reenviarText = document.getElementById('reenviarTexto');
    let cooldown = <?= $secsCooldown ?>;

    if (cooldown > 0) {
        const cooldownInterval = setInterval(() => {
            cooldown--;
            if (cooldown <= 0) {
                clearInterval(cooldownInterval);
                reenviarText.textContent = '¿No recibiste el código? Reenviar';
                btnReenviar.disabled = false;
            } else {
                reenviarText.textContent = 'Reenviar en ' + cooldown + 's';
            }
        }, 1000);
    }
})();
</script>
</body>
</html>
