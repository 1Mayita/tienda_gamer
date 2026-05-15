<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta — AutoZone</title>
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
    header('Location: ' . BASE_URL . 'views/client/dashboard.php');
    exit;
}
$flash = getFlash();
?>

<div class="auth-container">
    <div class="auth-left d-none d-lg-flex">
        <div class="auth-left-content">
            <a href="../../index.php" class="brand-logo mb-5 d-block">
                <span class="brand-icon">⬡</span> AUTO<span class="brand-accent">ZONE</span>
            </a>
            <h2 class="auth-tagline">Únete a la<br><span class="brand-accent">comunidad</span></h2>
            <p class="auth-desc">Crea tu cuenta y accede a todos los beneficios exclusivos de AutoZone.</p>
            <div class="auth-features mt-4">
                <div class="auth-feature-item">✓ Registro gratuito y sin compromisos</div>
                <div class="auth-feature-item">✓ Acceso inmediato al catálogo</div>
                <div class="auth-feature-item">✓ Guarda tus favoritos</div>
                <div class="auth-feature-item">✓ Historial de compras</div>
            </div>
        </div>
        <div class="auth-left-bg"></div>
    </div>

    <div class="auth-right">
        <div class="auth-form-wrap">
            <div class="d-flex align-items-center justify-content-between mb-5">
                <h1 class="auth-title">Crear Cuenta</h1>
                <a href="../../index.php" class="auth-back">← Inicio</a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mb-4">
                <?= htmlspecialchars($flash['mensaje']) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="../../controllers/AuthController.php" id="authForm" novalidate>
                <input type="hidden" name="accion" value="registro">

                <div class="auth-field mb-3">
                    <label class="auth-label">Nombre completo</label>
                    <input type="text" name="nombre" class="auth-input" placeholder="Juan Pérez" required maxlength="100">
                </div>

                <div class="auth-field mb-3">
                    <label class="auth-label">Correo electrónico</label>
                    <input type="email" name="correo" class="auth-input" placeholder="tu@correo.com" required>
                </div>

                <div class="auth-field mb-3">
                    <label class="auth-label">Contraseña</label>
                    <div class="position-relative">
                        <input type="password" name="contrasena" id="contrasena" class="auth-input" placeholder="Mínimo 8 caracteres" required minlength="8">
                        <button type="button" class="toggle-pass auth-eye" data-target="#contrasena">👁️</button>
                    </div>
                    <div class="password-strength mt-2" id="pwStrength"></div>
                    <ul class="pw-rules mt-2 mb-0 ps-3" id="pwRules" style="font-size:.78rem; color:#888; list-style:none; padding-left:0;">
                        <li id="rule-len">✗ Mínimo 8 caracteres</li>
                        <li id="rule-upper">✗ Al menos una mayúscula</li>
                        <li id="rule-num">✗ Al menos un número</li>
                        <li id="rule-special">✗ Al menos un carácter especial (@, #, $, !...)</li>
                    </ul>
                </div>

                <div class="auth-field mb-4">
                    <label class="auth-label">Confirmar contraseña</label>
                    <div class="position-relative">
                        <input type="password" name="confirmar" id="confirmar" class="auth-input" placeholder="Repite tu contraseña" required>
                        <button type="button" class="toggle-pass auth-eye" data-target="#confirmar">👁️</button>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" class="form-check-input" id="terminos" name="terminos" required>
                    <label class="form-check-label auth-check-label" for="terminos">
                        Acepto los <a href="#" class="auth-link">Términos de Servicio</a> y la <a href="#" class="auth-link">Política de Privacidad</a>
                    </label>
                    <div class="invalid-feedback" style="display:none; color:#e8272b; font-size:.78rem;" id="terminosError">
                        Debes aceptar los términos y condiciones para continuar.
                    </div>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-3 auth-submit-btn">
                    Crear Cuenta →
                </button>
            </form>

            <p class="auth-switch mt-4 text-center">
                ¿Ya tienes cuenta?
                <a href="login.php" class="auth-link">Inicia sesión</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
// ---- INDICADOR DE FORTALEZA Y REGLAS DE CONTRASEÑA ----
const pwInput = document.getElementById('contrasena');
const pwStrength = document.getElementById('pwStrength');
const rules = {
    'rule-len':     pw => pw.length >= 8,
    'rule-upper':   pw => /[A-Z]/.test(pw),
    'rule-num':     pw => /[0-9]/.test(pw),
    'rule-special': pw => /[^A-Za-z0-9]/.test(pw),
};
const ruleLabels = {
    'rule-len':     'Mínimo 8 caracteres',
    'rule-upper':   'Al menos una mayúscula',
    'rule-num':     'Al menos un número',
    'rule-special': 'Al menos un carácter especial (@, #, $, !...)',
};

pwInput?.addEventListener('input', function () {
    const pw = this.value;
    let score = 0;
    for (const [id, fn] of Object.entries(rules)) {
        const el = document.getElementById(id);
        const ok = fn(pw);
        if (ok) score++;
        if (el) {
            el.textContent = (ok ? '✓ ' : '✗ ') + ruleLabels[id];
            el.style.color  = ok ? '#22c55e' : '#888';
            el.style.fontWeight = ok ? '600' : '400';
        }
    }
    const colors = ['','#e8272b','#f97316','#eab308','#22c55e'];
    const labels = ['','Muy débil','Débil','Buena','Fuerte'];
    pwStrength.innerHTML = score > 0
        ? `<div style="height:4px;border-radius:2px;background:${colors[score]};width:${score*25}%;transition:all .3s"></div>
           <span style="font-size:.75rem;color:${colors[score]}">${labels[score]}</span>`
        : '';
});

// ---- VALIDACIÓN AL ENVIAR ----
document.getElementById('authForm')?.addEventListener('submit', function (e) {
    const pw      = document.getElementById('contrasena').value;
    const confirm = document.getElementById('confirmar').value;
    const terminos = document.getElementById('terminos');
    const terminosError = document.getElementById('terminosError');
    let valid = true;

    // Validar contraseña fuerte
    const allRulesOk = Object.values(rules).every(fn => fn(pw));
    if (!allRulesOk) {
        e.preventDefault();
        // Disparar el input para mostrar las reglas en rojo
        pwInput.dispatchEvent(new Event('input'));
        pwInput.focus();
        const firstFail = Object.entries(rules).find(([, fn]) => !fn(pw));
        const msg = firstFail ? '⚠️ ' + ruleLabels[firstFail[0]] : '⚠️ La contraseña no cumple los requisitos.';
        showToastReg(msg, 'error');
        valid = false;
    }

    // Validar que coincidan
    if (valid && pw !== confirm) {
        e.preventDefault();
        document.getElementById('confirmar').focus();
        showToastReg('⚠️ Las contraseñas no coinciden.', 'error');
        valid = false;
    }

    // Validar términos
    if (!terminos.checked) {
        e.preventDefault();
        terminosError.style.display = 'block';
        terminos.closest('.form-check').scrollIntoView({ behavior: 'smooth', block: 'center' });
        showToastReg('⚠️ Debes aceptar los Términos y Condiciones.', 'error');
        valid = false;
    } else {
        terminosError.style.display = 'none';
    }
});

document.getElementById('terminos')?.addEventListener('change', function () {
    document.getElementById('terminosError').style.display = this.checked ? 'none' : 'block';
});

// Toast local para el registro (por si main.js no está cargado aún)
function showToastReg(msg, tipo) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    const bg = tipo === 'success' ? '#15803d' : tipo === 'error' ? '#e8272b' : '#2563eb';
    toast.style.cssText = `background:${bg};color:#fff;padding:14px 22px;border-radius:10px;font-size:.9rem;font-weight:500;max-width:340px;box-shadow:0 8px 24px rgba(0,0,0,.4);`;
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 4000);
}
</script>
</body>
</html>
