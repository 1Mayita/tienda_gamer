<?php
// ============================================
//  AUTOZONE - Funciones Auxiliares
// ============================================

// Iniciar sesión de forma segura
function iniciarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false, // true en producción con HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// Verificar si el usuario está logueado
function estaLogueado(): bool {
    iniciarSesion();
    return isset($_SESSION['id_usuario']) && isset($_SESSION['2fa_ok']) && $_SESSION['2fa_ok'] === true;
}

// Verificar rol del usuario
function esAdmin(): bool {
    return estaLogueado() && $_SESSION['rol'] === 'admin';
}

// Redirigir con protección de rutas
function protegerRuta(string $rolRequerido = 'cliente'): void {
    if (!estaLogueado()) {
        header('Location: ' . BASE_URL . 'views/auth/login.php');
        exit;
    }
    if ($rolRequerido === 'admin' && !esAdmin()) {
        header('Location: ' . BASE_URL . 'views/client/dashboard.php');
        exit;
    }
}

// Sanitizar entrada del usuario
function sanitizar(string $dato): string {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Generar código 2FA aleatorio de 6 dígitos
function generarCodigo2FA(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Formatear precio en dólares
function formatearPrecio(float $precio): string {
    return '$' . number_format($precio, 2, '.', ',');
}

// Mensaje flash para sesión
function setFlash(string $tipo, string $mensaje): void {
    iniciarSesion();
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash(): ?array {
    iniciarSesion();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Carrito: agregar producto
function agregarAlCarrito(int $idProducto, int $cantidad = 1): void {
    iniciarSesion();
    if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
    if (isset($_SESSION['carrito'][$idProducto])) {
        $_SESSION['carrito'][$idProducto] += $cantidad;
    } else {
        $_SESSION['carrito'][$idProducto] = $cantidad;
    }
}

// Carrito: total de ítems
function totalCarrito(): int {
    iniciarSesion();
    if (!isset($_SESSION['carrito'])) return 0;
    return array_sum($_SESSION['carrito']);
}

// ============================================
//  FUNCIONES 2FA PROFESIONAL
// ============================================

/**
 * Genera un OTP de 6 dígitos, lo hashea con bcrypt y lo guarda en DB.
 * Devuelve el código en texto plano (para enviar por email).
 */
function generarYGuardarOTP(PDO $db, int $idUsuario): string {
    $codigo    = generarCodigo2FA();
    $hash      = password_hash($codigo, PASSWORD_BCRYPT);
    $expira    = date('Y-m-d H:i:s', time() + (OTP_EXPIRACION_MINUTOS * 60));

    $stmt = $db->prepare('
        UPDATE Usuario 
        SET otp_hash     = ?,
            otp_expira   = ?,
            otp_intentos = 0,
            codigo_2fa   = NULL
        WHERE id_usuario = ?
    ');
    $stmt->execute([$hash, $expira, $idUsuario]);

    return $codigo;
}

/**
 * Valida un código OTP ingresado contra la DB.
 * Retorna ['ok' => bool, 'error' => string, 'restantes' => int]
 */
function validarOTP(PDO $db, int $idUsuario, string $codigoIngresado): array {
    $stmt = $db->prepare('SELECT otp_hash, otp_expira, otp_intentos FROM Usuario WHERE id_usuario = ?');
    $stmt->execute([$idUsuario]);
    $row = $stmt->fetch();

    if (!$row || empty($row['otp_hash'])) {
        return ['ok' => false, 'error' => 'No hay código OTP activo. Inicia sesión nuevamente.', 'restantes' => 0];
    }

    // Verificar si está bloqueado por intentos
    if ($row['otp_intentos'] >= OTP_MAX_INTENTOS) {
        limpiarOTP($db, $idUsuario);
        return ['ok' => false, 'error' => 'Demasiados intentos fallidos. Inicia sesión nuevamente.', 'restantes' => 0];
    }

    // Verificar expiración
    if (strtotime($row['otp_expira']) < time()) {
        limpiarOTP($db, $idUsuario);
        return ['ok' => false, 'error' => 'El código ha expirado. Solicita uno nuevo.', 'restantes' => 0];
    }

    // Verificar código con bcrypt
    if (!password_verify($codigoIngresado, $row['otp_hash'])) {
        // Incrementar intentos
        $nuevosIntentos = $row['otp_intentos'] + 1;
        $db->prepare('UPDATE Usuario SET otp_intentos = ? WHERE id_usuario = ?')
           ->execute([$nuevosIntentos, $idUsuario]);

        $restantes = OTP_MAX_INTENTOS - $nuevosIntentos;
        if ($restantes <= 0) {
            limpiarOTP($db, $idUsuario);
            return ['ok' => false, 'error' => 'Demasiados intentos fallidos. Inicia sesión nuevamente.', 'restantes' => 0];
        }

        return [
            'ok'        => false,
            'error'     => "Código incorrecto. Te quedan $restantes intento(s).",
            'restantes' => $restantes,
        ];
    }

    // ✅ Código correcto
    return ['ok' => true, 'error' => '', 'restantes' => OTP_MAX_INTENTOS];
}

/**
 * Limpia todos los datos OTP de un usuario.
 */
function limpiarOTP(PDO $db, int $idUsuario): void {
    $db->prepare('
        UPDATE Usuario 
        SET otp_hash     = NULL,
            otp_expira   = NULL,
            otp_intentos = 0,
            codigo_2fa   = NULL
        WHERE id_usuario = ?
    ')->execute([$idUsuario]);
}

/**
 * Envía el código OTP por correo electrónico usando PHPMailer.
 * Retorna true si se envió correctamente, false si falló.
 */
function enviarCorreoOTP(string $correo, string $nombre, string $codigo): bool {
    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($correo, $nombre);

        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = '🔐 Código de verificación — AutoZone';
        $mail->Body    = generarPlantillaOTP($nombre, $codigo);
        $mail->AltBody = "Hola $nombre, tu código de verificación es: $codigo. Expira en " . OTP_EXPIRACION_MINUTOS . " minutos.";

        $mail->send();
        return true;

    } catch (PHPMailer\PHPMailer\Exception $e) {
        // En desarrollo, loguear el error
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Genera la plantilla HTML del correo con el código OTP.
 */
function generarPlantillaOTP(string $nombre, string $codigo): string {
    $expMin = OTP_EXPIRACION_MINUTOS;
    $digitos = str_split($codigo);
    $digitosHTML = '';
    foreach ($digitos as $d) {
        $digitosHTML .= "<span style=\"display:inline-block;width:42px;height:52px;line-height:52px;text-align:center;background:#1c1c26;color:#e8272b;font-size:28px;font-weight:700;border-radius:8px;margin:0 3px;font-family:'Courier New',monospace;border:1px solid rgba(232,39,43,0.3);\">$d</span>";
    }

    return "
    <div style=\"background:#0a0a0f;padding:40px 0;font-family:'Segoe UI',Arial,sans-serif;\">
        <div style=\"max-width:460px;margin:0 auto;background:#111118;border-radius:16px;border:1px solid rgba(255,255,255,0.06);overflow:hidden;\">
            
            <!-- Header -->
            <div style=\"background:linear-gradient(135deg,#1a0a0b,#0f0810);padding:32px;text-align:center;border-bottom:1px solid rgba(232,39,43,0.15);\">
                <div style=\"font-size:28px;margin-bottom:8px;\">⬡</div>
                <span style=\"font-size:22px;font-weight:700;color:#fff;letter-spacing:2px;\">AUTO</span><span style=\"color:#e8272b;font-size:22px;font-weight:700;letter-spacing:2px;\">ZONE</span>
            </div>
            
            <!-- Body -->
            <div style=\"padding:36px 32px;\">
                <h2 style=\"color:#fff;font-size:20px;margin:0 0 8px;font-weight:600;\">Verificación de Identidad</h2>
                <p style=\"color:#888899;font-size:14px;line-height:1.6;margin:0 0 28px;\">
                    Hola <strong style=\"color:#f0f0f0;\">$nombre</strong>, ingresa el siguiente código para completar tu inicio de sesión:
                </p>

                <!-- Código OTP -->
                <div style=\"text-align:center;padding:24px 0;background:rgba(232,39,43,0.05);border-radius:12px;margin-bottom:24px;border:1px solid rgba(232,39,43,0.1);\">
                    <div style=\"margin-bottom:12px;\">
                        $digitosHTML
                    </div>
                    <p style=\"color:#888899;font-size:12px;margin:8px 0 0;\">⏱ Válido por <strong style=\"color:#f0f0f0;\">$expMin minutos</strong></p>
                </div>

                <!-- Aviso -->
                <div style=\"background:rgba(255,255,255,0.03);border-radius:8px;padding:16px;border-left:3px solid #e8272b;\">
                    <p style=\"color:#888899;font-size:13px;line-height:1.5;margin:0;\">
                        🔒 Si no solicitaste este código, ignora este mensaje. Tu cuenta permanece segura.
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div style=\"padding:20px 32px;border-top:1px solid rgba(255,255,255,0.04);text-align:center;\">
                <p style=\"color:#555566;font-size:11px;margin:0;\">© " . date('Y') . " AutoZone · Sistema de Autenticación Segura</p>
            </div>
        </div>
    </div>";
}

/**
 * Envía correo de bienvenida al newsletter.
 */
function enviarCorreoNewsletter(string $correo): bool {
    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = '🚗 ¡Bienvenido al Newsletter de AutoZone!';
        $mail->Body    = generarPlantillaNewsletter($correo);
        $mail->AltBody = "¡Gracias por suscribirte a AutoZone! Te notificaremos cuando tengamos nuevos vehículos y ofertas exclusivas.";

        $mail->send();
        return true;
    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("Error newsletter PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Plantilla HTML del correo de bienvenida al newsletter.
 */
function generarPlantillaNewsletter(string $correo): string {
    $year = date('Y');
    return "
    <div style=\"background:#0a0a0f;padding:40px 0;font-family:'Segoe UI',Arial,sans-serif;\">
        <div style=\"max-width:480px;margin:0 auto;background:#111118;border-radius:16px;border:1px solid rgba(255,255,255,0.06);overflow:hidden;\">

            <!-- Header -->
            <div style=\"background:linear-gradient(135deg,#1a0a0b,#0f0810);padding:36px 32px;text-align:center;border-bottom:1px solid rgba(232,39,43,0.15);\">
                <div style=\"font-size:32px;margin-bottom:10px;\">⬡</div>
                <span style=\"font-size:24px;font-weight:700;color:#fff;letter-spacing:2px;\">AUTO</span><span style=\"color:#e8272b;font-size:24px;font-weight:700;letter-spacing:2px;\">ZONE</span>
            </div>

            <!-- Body -->
            <div style=\"padding:36px 32px;\">
                <h2 style=\"color:#fff;font-size:22px;margin:0 0 12px;font-weight:700;\">¡Ya eres parte de AutoZone! 🎉</h2>
                <p style=\"color:#888899;font-size:14px;line-height:1.7;margin:0 0 24px;\">
                    Gracias por suscribirte a nuestro newsletter. A partir de ahora serás el primero en enterarte de:
                </p>

                <div style=\"background:rgba(232,39,43,0.05);border-radius:12px;padding:20px 24px;margin-bottom:24px;border:1px solid rgba(232,39,43,0.1);\">
                    <div style=\"display:flex;align-items:center;margin-bottom:12px;\">
                        <span style=\"font-size:18px;margin-right:12px;\">🚗</span>
                        <span style=\"color:#f0f0f0;font-size:14px;font-weight:600;\">Nuevos vehículos en catálogo</span>
                    </div>
                    <div style=\"display:flex;align-items:center;margin-bottom:12px;\">
                        <span style=\"font-size:18px;margin-right:12px;\">🏷️</span>
                        <span style=\"color:#f0f0f0;font-size:14px;font-weight:600;\">Ofertas y promociones exclusivas</span>
                    </div>
                    <div style=\"display:flex;align-items:center;margin-bottom:12px;\">
                        <span style=\"font-size:18px;margin-right:12px;\">⭐</span>
                        <span style=\"color:#f0f0f0;font-size:14px;font-weight:600;\">Lanzamientos y modelos limitados</span>
                    </div>
                    <div style=\"display:flex;align-items:center;\">
                        <span style=\"font-size:18px;margin-right:12px;\">💳</span>
                        <span style=\"color:#f0f0f0;font-size:14px;font-weight:600;\">Planes de financiamiento especiales</span>
                    </div>
                </div>

                <div style=\"background:rgba(255,255,255,0.03);border-radius:8px;padding:14px 16px;border-left:3px solid #e8272b;\">
                    <p style=\"color:#888899;font-size:12px;line-height:1.5;margin:0;\">
                        📧 Recibirás nuestras notificaciones en <strong style=\"color:#f0f0f0;\">$correo</strong>.
                        Si no deseas seguir recibiendo correos, puedes ignorar futuros mensajes.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div style=\"padding:20px 32px;border-top:1px solid rgba(255,255,255,0.04);text-align:center;\">
                <p style=\"color:#555566;font-size:11px;margin:0;\">© $year AutoZone · Bolivia · Vehículos de Alto Rendimiento</p>
            </div>
        </div>
    </div>";
}

/**
 * Enmascara un correo: ejemplo@gmail.com → ej****@gmail.com
 */
function enmascararCorreo(string $correo): string {
    $partes = explode('@', $correo);
    if (count($partes) !== 2) return '***@***.com';
    $local   = $partes[0];
    $dominio = $partes[1];
    $visible = min(2, strlen($local));
    return substr($local, 0, $visible) . str_repeat('*', max(4, strlen($local) - $visible)) . '@' . $dominio;
}
