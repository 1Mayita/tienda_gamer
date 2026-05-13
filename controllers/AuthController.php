<?php
// ============================================
//  AUTOZONE - Controlador de Autenticación
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funciones.php';

iniciarSesion();

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {

    // ------------------------------------------
    // REGISTRO DE USUARIO
    // ------------------------------------------
    case 'registro':
        $nombre   = sanitizar($_POST['nombre']   ?? '');
        $correo   = sanitizar($_POST['correo']   ?? '');
        $password = $_POST['contrasena']          ?? '';
        $confirm  = $_POST['confirmar']           ?? '';

        // Validaciones
        if (empty($nombre) || empty($correo) || empty($password)) {
            setFlash('error', 'Todos los campos son obligatorios.');
            header('Location: ' . BASE_URL . 'views/auth/registro.php');
            exit;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Correo electrónico inválido.');
            header('Location: ' . BASE_URL . 'views/auth/registro.php');
            exit;
        }
        if (strlen($password) < 8) {
            setFlash('error', 'La contraseña debe tener al menos 8 caracteres.');
            header('Location: ' . BASE_URL . 'views/auth/registro.php');
            exit;
        }
        if ($password !== $confirm) {
            setFlash('error', 'Las contraseñas no coinciden.');
            header('Location: ' . BASE_URL . 'views/auth/registro.php');
            exit;
        }

        $db = getDB();
        // Verificar correo duplicado
        $stmt = $db->prepare('SELECT id_usuario FROM Usuario WHERE correo = ?');
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            setFlash('error', 'Este correo ya está registrado.');
            header('Location: ' . BASE_URL . 'views/auth/registro.php');
            exit;
        }

        // Hashear contraseña con Bcrypt
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO Usuario (nombre, correo, contrasena, rol) VALUES (?, ?, ?, "cliente")');
        $stmt->execute([$nombre, $correo, $hash]);

        setFlash('success', '¡Cuenta creada exitosamente! Por favor inicia sesión.');
        header('Location: ' . BASE_URL . 'views/auth/login.php');
        exit;

    // ------------------------------------------
    // INICIO DE SESIÓN (Paso 1)
    // ------------------------------------------
    case 'login':
        $correo   = sanitizar($_POST['correo']   ?? '');
        $password = $_POST['contrasena']          ?? '';

        if (empty($correo) || empty($password)) {
            setFlash('error', 'Correo y contraseña son requeridos.');
            header('Location: ' . BASE_URL . 'views/auth/login.php');
            exit;
        }

        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM Usuario WHERE correo = ? LIMIT 1');
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($password, $usuario['contrasena'])) {
            setFlash('error', 'Credenciales incorrectas. Intenta nuevamente.');
            header('Location: ' . BASE_URL . 'views/auth/login.php');
            exit;
        }

        // Generar código 2FA y guardarlo en sesión temporal
        $codigo = generarCodigo2FA();
        $stmt   = $db->prepare('UPDATE Usuario SET codigo_2fa = ? WHERE id_usuario = ?');
        $stmt->execute([$codigo, $usuario['id_usuario']]);

        // Guardar datos temporales en sesión para paso 2FA
        $_SESSION['pre_2fa'] = [
            'id_usuario' => $usuario['id_usuario'],
            'nombre'     => $usuario['nombre'],
            'correo'     => $usuario['correo'],
            'rol'        => $usuario['rol'],
            'codigo_2fa' => $codigo,
        ];

        // En producción: enviar por email. Aquí lo mostramos para demo.
        header('Location: ' . BASE_URL . 'views/auth/verificar2fa.php');
        exit;

    // ------------------------------------------
    // VERIFICAR CÓDIGO 2FA (Paso 2)
    // ------------------------------------------
    case 'verificar2fa':
        $codigoIngresado = sanitizar($_POST['codigo_2fa'] ?? '');

        if (!isset($_SESSION['pre_2fa'])) {
            header('Location: ' . BASE_URL . 'views/auth/login.php');
            exit;
        }

        $pre = $_SESSION['pre_2fa'];

        if ($codigoIngresado !== $pre['codigo_2fa']) {
            setFlash('error', 'Código 2FA incorrecto. Intenta de nuevo.');
            header('Location: ' . BASE_URL . 'views/auth/verificar2fa.php');
            exit;
        }

        // 2FA correcto: iniciar sesión real
        session_regenerate_id(true);
        $_SESSION['id_usuario'] = $pre['id_usuario'];
        $_SESSION['nombre']     = $pre['nombre'];
        $_SESSION['correo']     = $pre['correo'];
        $_SESSION['rol']        = $pre['rol'];
        $_SESSION['2fa_ok']     = true;
        unset($_SESSION['pre_2fa']);

        // Limpiar código 2FA de la DB
        $db = getDB();
        $stmt = $db->prepare('UPDATE Usuario SET codigo_2fa = NULL, estado_2fa = 1 WHERE id_usuario = ?');
        $stmt->execute([$_SESSION['id_usuario']]);

        setFlash('success', '¡Bienvenido, ' . $_SESSION['nombre'] . '!');

        if ($_SESSION['rol'] === 'admin') {
            header('Location: ' . BASE_URL . 'views/admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'views/client/dashboard.php');
        }
        exit;

    // ------------------------------------------
    // CERRAR SESIÓN
    // ------------------------------------------
    case 'logout':
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'views/auth/login.php');
        exit;

    default:
        header('Location: ' . BASE_URL . 'index.php');
        exit;
}
