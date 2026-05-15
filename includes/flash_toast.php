<?php
/* Convierte el flash de sesión en un toast pop-up.
   Incluir DESPUÉS de Bootstrap JS y toasts.js */
$_flashToast = getFlash();
if ($_flashToast): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    mostrarToast(
        <?= json_encode($_flashToast['tipo'] === 'success' ? 'success' : 'error') ?>,
        <?= json_encode($_flashToast['tipo'] === 'success' ? '¡Éxito!' : 'Error') ?>,
        <?= json_encode($_flashToast['mensaje']) ?>
    );
});
</script>
<?php endif; ?>
