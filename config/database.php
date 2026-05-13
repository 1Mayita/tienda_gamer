<?php
// ============================================
//  AUTOZONE - Configuración de Base de Datos
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'autozone_db');
define('DB_CHARSET', 'utf8mb4');

// Conexión PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

define('SITE_NAME', 'AutoZone');

// ── Detección automática de BASE_URL ──────────────────────────────────────────
if (!defined('BASE_URL')) {
    if (isset($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        // Ruta física al directorio raíz del proyecto (un nivel sobre /config)
        $projDir  = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        $docRoot  = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
        // Normalizar a minúsculas para comparación en Windows
        $urlPath  = str_ireplace($docRoot, '', $projDir);
        define('BASE_URL', $protocol . '://' . $host . $urlPath . '/');
    } else {
        // Fallback para CLI / contextos sin servidor
        define('BASE_URL', 'http://localhost/autozone/autozone/');
    }
}
// ─────────────────────────────────────────────────────────────────────────────

// Rutas de uploads (físicas y URL)
define('UPLOADS_PATH',       realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR);
define('UPLOADS_URL',        BASE_URL . 'uploads/');
define('IMG_PRODUCTOS_PATH', UPLOADS_PATH . 'productos' . DIRECTORY_SEPARATOR);
define('IMG_PRODUCTOS_URL',  UPLOADS_URL  . 'productos/');
define('IMG_DEFAULT_URL',    UPLOADS_URL  . 'default.svg');
