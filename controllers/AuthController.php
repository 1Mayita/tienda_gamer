<?php
// ============================================
//  AUTOZONE - Controlador de Autenticación
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
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

        // Generar OTP seguro: hasheado en DB, texto plano solo para email
        $codigoPlano = generarYGuardarOTP($db, $usuario['id_usuario']);

        // Enviar OTP por correo electrónico
        $enviado = enviarCorreoOTP($usuario['correo'], $usuario['nombre'], $codigoPlano);

        // Guardar datos temporales en sesión para paso 2FA
        // ⚠️ NO almacenamos el código en texto plano en la sesión
        $_SESSION['pre_2fa'] = [
            'id_usuario'     => $usuario['id_usuario'],
            'nombre'         => $usuario['nombre'],
            'correo'         => $usuario['correo'],
            'rol'            => $usuario['rol'],
            'otp_timestamp'  => time(),
            'ultimo_reenvio' => time(),
        ];

        if ($enviado) {
            setFlash('success', 'Se ha enviado un código de verificación a tu correo.');
        } else {
            setFlash('warning', 'No se pudo enviar el correo. Verifica la configuración SMTP.');
        }

        header('Location: ' . BASE_URL . 'views/auth/verificar2fa.php');
        exit;

    // ------------------------------------------
    // VERIFICAR CÓDIGO 2FA (Paso 2)
    // ------------------------------------------
    case 'verificar2fa':
        $codigoIngresado = sanitizar($_POST['codigo_2fa'] ?? '');

        if (!isset($_SESSION['pre_2fa'])) {
            setFlash('error', 'Sesión expirada. Inicia sesión nuevamente.');
            header('Location: ' . BASE_URL . 'views/auth/login.php');
            exit;
        }

        $pre = $_SESSION['pre_2fa'];
        $db  = getDB();

        // Validar OTP con todas las verificaciones de seguridad
        $resultado = validarOTP($db, $pre['id_usuario'], $codigoIngresado);

        if (!$resultado['ok']) {
            // Si los intentos se agotaron o el código expiró, destruir sesión pre-2FA
            if ($resultado['restantes'] <= 0) {
                unset($_SESSION['pre_2fa']);
                setFlash('error', $resultado['error']);
                header('Location: ' . BASE_URL . 'views/auth/login.php');
                exit;
            }

            setFlash('error', $resultado['error']);
            header('Location: ' . BASE_URL . 'views/auth/verificar2fa.php');
            exit;
        }

        // ✅ 2FA correcto: iniciar sesión real
        session_regenerate_id(true);
        $_SESSION['id_usuario'] = $pre['id_usuario'];
        $_SESSION['nombre']     = $pre['nombre'];
        $_SESSION['correo']     = $pre['correo'];
        $_SESSION['rol']        = $pre['rol'];
        $_SESSION['2fa_ok']     = true;
        unset($_SESSION['pre_2fa']);

        // Limpiar OTP de la DB y marcar 2FA como verificado
        limpiarOTP($db, $_SESSION['id_usuario']);
        $stmt = $db->prepare('UPDATE Usuario SET estado_2fa = 1 WHERE id_usuario = ?');
        $stmt->execute([$_SESSION['id_usuario']]);

        setFlash('success', '¡Bienvenido, ' . $_SESSION['nombre'] . '!');

        if ($_SESSION['rol'] === 'admin') {
            header('Location: ' . BASE_URL . 'views/admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'views/client/dashboard.php');
        }
        exit;

    // ------------------------------------------
    // REENVIAR CÓDIGO 2FA
    // ------------------------------------------
    case 'reenviar2fa':
        if (!isset($_SESSION['pre_2fa'])) {
            header('Location: ' . BASE_URL . 'views/auth/login.php');
            exit;
        }

        $pre = $_SESSION['pre_2fa'];

        // Rate limit: mínimo OTP_REENVIO_COOLDOWN segundos entre reenvíos
        $tiempoDesdeUltimo = time() - ($pre['ultimo_reenvio'] ?? 0);
        if ($tiempoDesdeUltimo < OTP_REENVIO_COOLDOWN) {
            $restante = OTP_REENVIO_COOLDOWN - $tiempoDesdeUltimo;
            setFlash('error', "Espera {$restante} segundos antes de solicitar otro código.");
            header('Location: ' . BASE_URL . 'views/auth/verificar2fa.php');
            exit;
        }

        $db = getDB();

        // Generar nuevo OTP
        $codigoPlano = generarYGuardarOTP($db, $pre['id_usuario']);

        // Enviar por correo
        $enviado = enviarCorreoOTP($pre['correo'], $pre['nombre'], $codigoPlano);

        // Actualizar timestamps en sesión
        $_SESSION['pre_2fa']['otp_timestamp']  = time();
        $_SESSION['pre_2fa']['ultimo_reenvio'] = time();

        if ($enviado) {
            setFlash('success', 'Se ha enviado un nuevo código a tu correo.');
        } else {
            setFlash('warning', 'No se pudo reenviar el correo. Verifica la configuración SMTP.');
        }

        header('Location: ' . BASE_URL . 'views/auth/verificar2fa.php');
        exit;

    // ------------------------------------------
    // CREAR USUARIO (super admin)
    // ------------------------------------------
    case 'crear_usuario':
        protegerRuta('admin');
        $nombre   = sanitizar($_POST['nombre']    ?? '');
        $correo   = sanitizar($_POST['correo']    ?? '');
        $password = $_POST['contrasena']           ?? '';
        $rolesV   = ['cliente','premium','admin'];
        $rol      = in_array($_POST['rol'] ?? '', $rolesV) ? $_POST['rol'] : 'cliente';

        if (empty($nombre) || empty($correo) || strlen($password) < 8) {
            setFlash('error', 'Completa todos los campos. La contraseña debe tener al menos 8 caracteres.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Correo electrónico inválido.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }

        $db   = getDB();
        $stmt = $db->prepare('SELECT id_usuario FROM Usuario WHERE correo = ?');
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            setFlash('error', 'Ya existe un usuario con ese correo.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare('INSERT INTO Usuario (nombre,correo,contrasena,rol) VALUES (?,?,?,?)')
           ->execute([$nombre, $correo, $hash, $rol]);

        $labels = ['cliente'=>'Cliente','premium'=>'Cliente Premium','admin'=>'Administrador'];
        setFlash('success', "Usuario \"$nombre\" creado como {$labels[$rol]}.");
        header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
        exit;

    // ------------------------------------------
    // EDITAR USUARIO (super admin)
    // ------------------------------------------
    case 'editar_usuario':
        protegerRuta('admin');
        $id_target = (int)($_POST['id_usuario'] ?? 0);
        $nombre    = sanitizar($_POST['nombre']  ?? '');
        $correo    = sanitizar($_POST['correo']  ?? '');
        $password  = $_POST['contrasena']         ?? '';
        $rolesV    = ['cliente','premium','admin'];
        $rol       = in_array($_POST['rol'] ?? '', $rolesV) ? $_POST['rol'] : 'cliente';

        if ($id_target <= 0 || empty($nombre) || empty($correo)) {
            setFlash('error', 'Datos inválidos.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Correo electrónico inválido.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }

        $db = getDB();
        // Verificar correo duplicado en otro usuario
        $stmt = $db->prepare('SELECT id_usuario FROM Usuario WHERE correo = ? AND id_usuario != ?');
        $stmt->execute([$correo, $id_target]);
        if ($stmt->fetch()) {
            setFlash('error', 'Ese correo ya está en uso por otro usuario.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }

        if (!empty($password)) {
            if (strlen($password) < 8) {
                setFlash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
                header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
                exit;
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare('UPDATE Usuario SET nombre=?,correo=?,rol=?,contrasena=? WHERE id_usuario=?')
               ->execute([$nombre, $correo, $rol, $hash, $id_target]);
        } else {
            $db->prepare('UPDATE Usuario SET nombre=?,correo=?,rol=? WHERE id_usuario=?')
               ->execute([$nombre, $correo, $rol, $id_target]);
        }

        setFlash('success', "Usuario \"$nombre\" actualizado correctamente.");
        header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
        exit;

    // ------------------------------------------
    // CAMBIAR ROL DE USUARIO (admin)
    // ------------------------------------------
    case 'cambiar_rol':
        protegerRuta('admin');
        $id_target  = (int)($_POST['id_usuario'] ?? 0);
        $rolesValid = ['cliente', 'premium', 'admin'];
        $nuevo_rol  = in_array($_POST['rol'] ?? '', $rolesValid) ? $_POST['rol'] : 'cliente';

        if ($id_target <= 0 || $id_target === (int)$_SESSION['id_usuario']) {
            setFlash('error', 'Operación no permitida.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }

        $db   = getDB();
        $stmt = $db->prepare('UPDATE Usuario SET rol = ? WHERE id_usuario = ?');
        $stmt->execute([$nuevo_rol, $id_target]);

        $labels = ['cliente' => 'Cliente', 'premium' => 'Cliente Premium', 'admin' => 'Administrador'];
        setFlash('success', 'Rol actualizado a ' . $labels[$nuevo_rol] . ' correctamente.');
        header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
        exit;

    // ------------------------------------------
    // CAMBIAR ROL DE USUARIO AJAX (admin)
    // ------------------------------------------
    case 'cambiar_rol_ajax':
        header('Content-Type: application/json; charset=utf-8');
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            echo json_encode(['ok' => false, 'error' => 'No autorizado.']);
            exit;
        }

        $id_target  = (int)($_POST['id_usuario'] ?? 0);
        $rolesValid = ['cliente', 'premium', 'admin'];
        $nuevo_rol  = in_array($_POST['rol'] ?? '', $rolesValid) ? $_POST['rol'] : 'cliente';

        if ($id_target <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Usuario inválido.']);
            exit;
        }
        if ($id_target === (int)$_SESSION['id_usuario']) {
            echo json_encode(['ok' => false, 'error' => 'No puedes cambiar tu propio rol.']);
            exit;
        }

        $db   = getDB();
        $stmt = $db->prepare('UPDATE Usuario SET rol = ? WHERE id_usuario = ?');
        $stmt->execute([$nuevo_rol, $id_target]);

        $labels = ['cliente' => 'Cliente', 'premium' => '⭐ Premium', 'admin' => '🛡️ Admin'];
        $clases = ['cliente' => 'pagado',  'premium' => 'premium',    'admin' => 'entregado'];

        echo json_encode([
            'ok'      => true,
            'mensaje' => 'Rol actualizado correctamente.',
            'rol'     => $nuevo_rol,
            'label'   => $labels[$nuevo_rol],
            'clase'   => $clases[$nuevo_rol]
        ]);
        exit;

    // ------------------------------------------
    // ELIMINAR USUARIO (admin, solo sin compras)
    // ------------------------------------------
    case 'eliminar_usuario':
        protegerRuta('admin');
        $id_target = (int)($_POST['id_usuario'] ?? 0);

        if ($id_target <= 0 || $id_target === (int)$_SESSION['id_usuario']) {
            setFlash('error', 'No puedes eliminarte a ti mismo.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }

        $db = getDB();

        // Verificar que no tenga pedidos
        $stmt = $db->prepare('SELECT COUNT(*) FROM Venta WHERE id_usuario = ?');
        $stmt->execute([$id_target]);
        if ($stmt->fetchColumn() > 0) {
            setFlash('error', 'No se puede eliminar: el usuario tiene pedidos registrados.');
            header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
            exit;
        }

        // Eliminar favoritos y luego el usuario
        $db->prepare('DELETE FROM Favorito WHERE id_usuario = ?')->execute([$id_target]);
        $db->prepare('DELETE FROM Usuario  WHERE id_usuario = ?')->execute([$id_target]);

        setFlash('success', 'Usuario eliminado correctamente.');
        header('Location: ' . BASE_URL . 'views/admin/usuarios.php');
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
