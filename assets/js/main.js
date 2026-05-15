// ============================================
//  AUTOZONE — JavaScript Principal
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ---- NAVBAR SCROLL ----
    const nav = document.getElementById('mainNav');
    if (nav) {
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 60);
        });
    }

    // ---- SMOOTH SCROLL ----
    document.querySelectorAll('a.nav-scroll, a[href^="#"]').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#') && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    // ---- BUSCADOR DINÁMICO ----
    const buscador = document.getElementById('buscador');
    if (buscador) {
        buscador.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            const items = document.querySelectorAll('.producto-item');
            let visible = 0;
            items.forEach(item => {
                const nombre = item.dataset.nombre || '';
                const marca  = item.dataset.marca  || '';
                const cat    = item.dataset.cat    || '';
                const match  = nombre.includes(q) || marca.includes(q) || cat.includes(q) || q === '';
                item.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            // Mensaje si no hay resultados
            let noResults = document.getElementById('noResults');
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.id = 'noResults';
                noResults.className = 'col-12 text-center py-5';
                noResults.innerHTML = '<p style="color:#888;font-size:1.1rem">🔍 No se encontraron vehículos para "<strong id="qTerm"></strong>"</p>';
                document.getElementById('productosGrid')?.appendChild(noResults);
            }
            noResults.style.display = visible === 0 && q !== '' ? '' : 'none';
            const qTerm = document.getElementById('qTerm');
            if (qTerm) qTerm.textContent = q;
        });
    }

    // ---- VALIDACIÓN FORMULARIO CONTACTO ----
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const inputs = contactForm.querySelectorAll('[required]');
            let valid = true;
            inputs.forEach(inp => {
                inp.style.borderColor = '';
                if (!inp.value.trim()) {
                    inp.style.borderColor = '#e8272b';
                    valid = false;
                }
                if (inp.type === 'email' && inp.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(inp.value)) {
                    inp.style.borderColor = '#e8272b';
                    valid = false;
                }
            });
            if (valid) {
                showToast('✅ Mensaje enviado. Te contactaremos pronto.', 'success');
                contactForm.reset();
            } else {
                showToast('⚠️ Por favor completa todos los campos requeridos.', 'error');
            }
        });
    }

    // ---- VALIDACIÓN FORMULARIOS AUTH ----
    const authForm = document.getElementById('authForm');
    if (authForm) {
        authForm.addEventListener('submit', function (e) {
            const password = document.getElementById('contrasena');
            const confirm  = document.getElementById('confirmar');
            if (password && password.value.length < 8) {
                e.preventDefault();
                showToast('⚠️ La contraseña debe tener mínimo 8 caracteres.', 'error');
                password.focus();
                return;
            }
            if (confirm && password && password.value !== confirm.value) {
                e.preventDefault();
                showToast('⚠️ Las contraseñas no coinciden.', 'error');
                confirm.focus();
            }
        });
    }

    // ---- CANTIDAD CARRITO ----
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input  = this.closest('.qty-wrap').querySelector('.qty-input');
            const max    = parseInt(input.dataset.max || 99);
            let val = parseInt(input.value) || 1;
            if (this.dataset.action === 'plus')  val = Math.min(val + 1, max);
            if (this.dataset.action === 'minus') val = Math.max(val - 1, 1);
            input.value = val;
            // Si hay un subtotal para actualizar
            const row     = this.closest('tr') || this.closest('.cart-row');
            const priceEl = row?.querySelector('[data-price]');
            const subEl   = row?.querySelector('.subtotal-val');
            if (priceEl && subEl) {
                const price = parseFloat(priceEl.dataset.price);
                subEl.textContent = '$' + (price * val).toLocaleString('en-US', { minimumFractionDigits: 2 });
                updateCartTotal();
            }
        });
    });

    // ---- ACTUALIZAR TOTAL CARRITO ----
    function updateCartTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal-val').forEach(el => {
            total += parseFloat(el.textContent.replace(/[$,]/g,'')) || 0;
        });
        const totalEl = document.getElementById('cartTotal');
        if (totalEl) totalEl.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
    }

    // ---- TOAST ----
    function showToast(msg, tipo = 'info') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        const bg = tipo === 'success' ? '#15803d' : tipo === 'error' ? '#e8272b' : '#2563eb';
        toast.style.cssText = `background:${bg};color:#fff;padding:14px 22px;border-radius:10px;font-size:.9rem;font-weight:500;max-width:320px;box-shadow:0 8px 24px rgba(0,0,0,.4);animation:slideIn .3s ease;`;
        toast.textContent = msg;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 3500);
    }

    // ---- CONFIRMAR ELIMINACIÓN ----
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || '¿Estás seguro?')) {
                e.preventDefault();
            }
        });
    });

    // ---- CONTADOR 2FA ----
    const countdown = document.getElementById('countdown2fa');
    if (countdown) {
        let secs = parseInt(countdown.dataset.secs || 120);
        const btnVerificar = document.getElementById('btnVerificar');
        const interval = setInterval(() => {
            secs--;
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            countdown.textContent = `${m}:${s.toString().padStart(2,'0')}`;
            if (secs <= 30) {
                countdown.style.color = '#f97316';
            }
            if (secs <= 0) {
                clearInterval(interval);
                countdown.textContent = 'Código expirado';
                countdown.style.color = '#e8272b';
                if (btnVerificar) {
                    btnVerificar.disabled = true;
                    btnVerificar.textContent = 'Código expirado — Solicita uno nuevo';
                    btnVerificar.style.opacity = '0.5';
                }
                // Deshabilitar inputs OTP
                document.querySelectorAll('.otp-digit').forEach(inp => {
                    inp.disabled = true;
                    inp.style.opacity = '0.4';
                });
            }
        }, 1000);
    }

    // ---- MOSTRAR/OCULTAR PASSWORD ----
    document.querySelectorAll('.toggle-pass').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = document.querySelector(this.dataset.target);
            if (!target) return;
            const isPass = target.type === 'password';
            target.type = isPass ? 'text' : 'password';
            this.textContent = isPass ? '🙈' : '👁️';
        });
    });

    // ---- ANIMACIONES DE ENTRADA ----
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.product-card, .benefit-card, .cat-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(24px)';
        el.style.transition = 'opacity .5s ease, transform .5s ease';
        observer.observe(el);
    });
});

// ---- ESTILOS ANIMACIÓN TOAST ----
const style = document.createElement('style');
style.textContent = `@keyframes slideIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }`;
document.head.appendChild(style);

// ---- NEWSLETTER FOOTER ----
function suscribirNewsletter() {
    const input = document.getElementById('newsletterInput');
    const msg   = document.getElementById('newsletterMsg');
    const btn   = document.getElementById('newsletterBtn');
    if (!input || !msg) return;

    const correo = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!correo) {
        msg.style.display = 'block';
        msg.style.color   = '#e8272b';
        msg.textContent   = '⚠️ Por favor ingresa tu correo.';
        return;
    }
    if (!emailRegex.test(correo)) {
        msg.style.display = 'block';
        msg.style.color   = '#e8272b';
        msg.textContent   = '⚠️ Ingresa un correo válido.';
        return;
    }

    // Estado de carga
    btn.disabled         = true;
    btn.textContent      = '...';
    msg.style.display    = 'none';

    const fd = new FormData();
    fd.append('correo', correo);

    fetch('controllers/NewsletterController.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                btn.textContent      = '✓';
                btn.style.background = '#22c55e';
                input.disabled       = true;
                msg.style.display    = 'block';
                msg.style.color      = '#22c55e';
                msg.textContent      = '✅ ¡Gracias! Revisa tu correo, te enviamos una confirmación.';

                setTimeout(() => {
                    btn.disabled         = false;
                    btn.textContent      = '→';
                    btn.style.background = '';
                    input.disabled       = false;
                    input.value          = '';
                    setTimeout(() => { msg.style.display = 'none'; }, 3000);
                }, 6000);
            } else {
                btn.disabled    = false;
                btn.textContent = '→';
                msg.style.display = 'block';
                msg.style.color   = '#e8272b';
                msg.textContent   = '⚠️ ' + (data.error || 'No se pudo procesar. Intenta de nuevo.');
            }
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = '→';
            msg.style.display = 'block';
            msg.style.color   = '#e8272b';
            msg.textContent   = '⚠️ Error de conexión. Intenta de nuevo.';
        });
}
