/* =====================================================
   AutoZone — Sistema global de toasts / pop-ups
   Incluir DESPUÉS de Bootstrap JS
===================================================== */

// Crear el contenedor si no existe
(function() {
    if (!document.getElementById('toastContainer')) {
        const c = document.createElement('div');
        c.id = 'toastContainer';
        document.body.appendChild(c);
    }
})();

function mostrarToast(tipo, titulo, mensaje, duracion) {
    duracion = duracion || 5000;
    var iconos = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    var container = document.getElementById('toastContainer');
    if (!container) return;

    var toast = document.createElement('div');
    toast.className = 'az-toast' +
        (tipo === 'success' ? ' success' : tipo === 'warning' ? ' warning' : tipo === 'info' ? ' info' : '');
    toast.innerHTML =
        '<span class="az-toast-icon">' + (iconos[tipo] || 'ℹ️') + '</span>' +
        '<div class="az-toast-body">' +
            '<div class="az-toast-title">' + titulo + '</div>' +
            '<div class="az-toast-msg">' + mensaje + '</div>' +
        '</div>' +
        '<button class="az-toast-close" onclick="cerrarToast(this.closest(\'.az-toast\'))">✕</button>';

    container.appendChild(toast);
    setTimeout(function() { cerrarToast(toast); }, duracion);
}

function cerrarToast(el) {
    if (!el || !el.parentNode) return;
    el.style.animation = 'azSlideIn .3s ease reverse forwards';
    setTimeout(function() { if (el.parentNode) el.remove(); }, 280);
}
