<?php
// ============================================
//  AUTOZONE - Configuración de Correo (SMTP)
// ============================================
// Usa Gmail SMTP con contraseña de aplicación.
// Para generar la contraseña:
//   1. Entra a https://myaccount.google.com/security
//   2. Activa la "Verificación en dos pasos"
//   3. Ve a "Contraseñas de aplicaciones"
//   4. Crea una para "Correo" → "Otra (AutoZone)"
//   5. Copia la contraseña de 16 caracteres aquí abajo
// ============================================

define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'autozonemg@gmail.com');       // ← Tu Gmail
define('MAIL_PASSWORD',   'jrby qgsk sret nxmr');       // ← Contraseña de aplicación (16 chars)
define('MAIL_FROM_EMAIL', 'autozonemg@gmail.com');       // ← Mismo Gmail
define('MAIL_FROM_NAME',  'AutoZone Security');
define('MAIL_ENCRYPTION', 'tls');

// ── Configuración OTP ────────────────────────
define('OTP_EXPIRACION_MINUTOS', 5);    // Tiempo de vida del código
define('OTP_MAX_INTENTOS',       5);    // Intentos antes de bloquear
define('OTP_REENVIO_COOLDOWN',   60);   // Segundos entre reenvíos
