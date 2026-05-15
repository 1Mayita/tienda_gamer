<?php
// ============================================
//  AUTOZONE - Controlador de Productos
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funciones.php';

iniciarSesion();
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {

    // ------------------------------------------
    // CREAR PRODUCTO
    // ------------------------------------------
    case 'crear':
        protegerRuta('admin');
        $nombre      = sanitizar($_POST['nombre']      ?? '');
        $marca       = sanitizar($_POST['marca']       ?? '');
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        $precio      = (float)($_POST['precio']        ?? 0);
        $stock       = (int)($_POST['stock']           ?? 0);
        $id_cat      = (int)($_POST['id_categoria']    ?? 0);
        $estado      = (int)($_POST['estado']          ?? 1);
        $imagen      = 'default.svg';

        if (empty($nombre) || empty($marca) || $precio <= 0 || $id_cat <= 0) {
            setFlash('error', 'Completa todos los campos obligatorios.');
            header('Location: ' . BASE_URL . 'views/admin/productos.php?modal=crear');
            exit;
        }

        // Manejo de imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext        = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg','jpeg','png','webp'];
            $maxBytes   = 5 * 1024 * 1024; // 5 MB
            if (in_array($ext, $permitidos) && $_FILES['imagen']['size'] <= $maxBytes) {
                $imagen = uniqid('auto_') . '.' . $ext;
                move_uploaded_file($_FILES['imagen']['tmp_name'], IMG_PRODUCTOS_PATH . $imagen);
            } else {
                setFlash('error', 'Imagen inválida. Usa JPG/PNG/WEBP de hasta 5 MB.');
                header('Location: ' . BASE_URL . 'views/admin/productos.php?modal=crear');
                exit;
            }
        }

        $db   = getDB();
        $stmt = $db->prepare('INSERT INTO Producto (id_categoria,nombre,marca,descripcion,precio,stock,imagen,estado) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$id_cat,$nombre,$marca,$descripcion,$precio,$stock,$imagen,$estado]);

        setFlash('success', 'Producto creado exitosamente.');
        header('Location: ' . BASE_URL . 'views/admin/productos.php');
        exit;

    // ------------------------------------------
    // ACTUALIZAR PRODUCTO
    // ------------------------------------------
    case 'actualizar':
        protegerRuta('admin');
        $id          = (int)($_POST['id_producto']     ?? 0);
        $nombre      = sanitizar($_POST['nombre']      ?? '');
        $marca       = sanitizar($_POST['marca']       ?? '');
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        $precio      = (float)($_POST['precio']        ?? 0);
        $stock       = (int)($_POST['stock']           ?? 0);
        $id_cat      = (int)($_POST['id_categoria']    ?? 0);
        $estado      = (int)($_POST['estado']          ?? 1);

        if ($id <= 0 || empty($nombre) || $precio <= 0) {
            setFlash('error', 'Datos inválidos.');
            header('Location: ' . BASE_URL . 'views/admin/productos.php');
            exit;
        }

        $db   = getDB();
        // Verificar imagen actual
        $stmtImg = $db->prepare('SELECT imagen FROM Producto WHERE id_producto = ?');
        $stmtImg->execute([$id]);
        $actual = $stmtImg->fetchColumn();
        $imagen = $actual ?? 'default.jpg';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext        = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg','jpeg','png','webp'];
            $maxBytes   = 5 * 1024 * 1024; // 5 MB
            if (in_array($ext, $permitidos) && $_FILES['imagen']['size'] <= $maxBytes) {
                $nueva = uniqid('auto_') . '.' . $ext;
                move_uploaded_file($_FILES['imagen']['tmp_name'], IMG_PRODUCTOS_PATH . $nueva);
                // Eliminar imagen anterior si no es el placeholder
                $noEliminar = ['default.jpg', 'default.svg'];
                if (!in_array($imagen, $noEliminar) && file_exists(IMG_PRODUCTOS_PATH . $imagen)) {
                    unlink(IMG_PRODUCTOS_PATH . $imagen);
                }
                $imagen = $nueva;
            }
        }

        $stmt = $db->prepare('UPDATE Producto SET id_categoria=?,nombre=?,marca=?,descripcion=?,precio=?,stock=?,imagen=?,estado=? WHERE id_producto=?');
        $stmt->execute([$id_cat,$nombre,$marca,$descripcion,$precio,$stock,$imagen,$estado,$id]);

        setFlash('success', 'Producto actualizado correctamente.');
        header('Location: ' . BASE_URL . 'views/admin/productos.php');
        exit;

    // ------------------------------------------
    // ELIMINAR PRODUCTO
    // ------------------------------------------
    case 'eliminar':
        protegerRuta('admin');
        $id = (int)($_POST['id_producto'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'ID de producto inválido.');
            header('Location: ' . BASE_URL . 'views/admin/productos.php');
            exit;
        }

        $db   = getDB();
        $stmt = $db->prepare('UPDATE Producto SET estado = 0 WHERE id_producto = ?');
        $stmt->execute([$id]);

        setFlash('success', 'Producto desactivado correctamente.');
        header('Location: ' . BASE_URL . 'views/admin/productos.php');
        exit;

    // ------------------------------------------
    // CANCELAR PEDIDO (cliente, solo Pendiente)
    // ------------------------------------------
    case 'cancelar_pedido':
        protegerRuta('cliente');
        $id_venta  = (int)($_POST['id_venta'] ?? 0);
        $id_usuario = (int)$_SESSION['id_usuario'];

        if ($id_venta <= 0) {
            setFlash('error', 'Pedido inválido.');
            header('Location: ' . BASE_URL . 'views/client/dashboard.php');
            exit;
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM Venta WHERE id_venta = ? AND id_usuario = ?');
        $stmt->execute([$id_venta, $id_usuario]);
        $venta = $stmt->fetch();

        if (!$venta) {
            setFlash('error', 'Pedido no encontrado.');
            header('Location: ' . BASE_URL . 'views/client/dashboard.php');
            exit;
        }
        if ($venta['estado_venta'] !== 'Pendiente') {
            setFlash('error', 'Solo se pueden cancelar pedidos en estado Pendiente.');
            header('Location: ' . BASE_URL . 'views/client/dashboard.php');
            exit;
        }

        // Restaurar stock de cada producto
        $stmtDet = $db->prepare('SELECT id_producto, cantidad FROM Detalle_Venta WHERE id_venta = ?');
        $stmtDet->execute([$id_venta]);
        $items = $stmtDet->fetchAll();

        $db->beginTransaction();
        try {
            foreach ($items as $item) {
                $db->prepare('UPDATE Producto SET stock = stock + ? WHERE id_producto = ?')
                   ->execute([$item['cantidad'], $item['id_producto']]);
            }
            $db->prepare('DELETE FROM Detalle_Venta WHERE id_venta = ?')->execute([$id_venta]);
            $db->prepare('DELETE FROM Venta WHERE id_venta = ?')->execute([$id_venta]);
            $db->commit();
            setFlash('success', "Pedido #$id_venta cancelado. El stock ha sido restaurado.");
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'No se pudo cancelar el pedido. Intenta nuevamente.');
        }
        header('Location: ' . BASE_URL . 'views/client/dashboard.php');
        exit;

    // ------------------------------------------
    // ELIMINAR IMAGEN DEL PRODUCTO
    // ------------------------------------------
    case 'eliminar_imagen':
        protegerRuta('admin');
        $id = (int)($_POST['id_producto'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'ID de producto inválido.');
            header('Location: ' . BASE_URL . 'views/admin/productos.php');
            exit;
        }
        $db = getDB();
        $stmtImg = $db->prepare('SELECT imagen FROM Producto WHERE id_producto = ?');
        $stmtImg->execute([$id]);
        $imgActual = $stmtImg->fetchColumn();
        $noEliminar = ['default.jpg', 'default.svg'];
        if ($imgActual && !in_array($imgActual, $noEliminar) && file_exists(IMG_PRODUCTOS_PATH . $imgActual)) {
            unlink(IMG_PRODUCTOS_PATH . $imgActual);
        }
        $db->prepare('UPDATE Producto SET imagen = ? WHERE id_producto = ?')->execute(['default.svg', $id]);
        setFlash('success', 'Foto eliminada. Se usará la imagen por defecto.');
        header('Location: ' . BASE_URL . 'views/admin/productos.php');
        exit;

    // ------------------------------------------
    // REACTIVAR PRODUCTO
    // ------------------------------------------
    case 'reactivar':
        protegerRuta('admin');
        $id = (int)($_POST['id_producto'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'ID de producto inválido.');
            header('Location: ' . BASE_URL . 'views/admin/productos.php');
            exit;
        }
        $db = getDB();
        $db->prepare('UPDATE Producto SET estado = 1 WHERE id_producto = ?')->execute([$id]);
        setFlash('success', 'Vehículo reactivado correctamente.');
        header('Location: ' . BASE_URL . 'views/admin/productos.php');
        exit;

    // ------------------------------------------
    // AGREGAR AL CARRITO
    // ------------------------------------------
    case 'agregar_carrito':
        if (!estaLogueado()) {
            header('Location: ' . BASE_URL . 'views/auth/login.php');
            exit;
        }
        $id_producto = (int)($_POST['id_producto'] ?? 0);
        $cantidad    = max(1, (int)($_POST['cantidad'] ?? 1));

        // Verificar stock
        $db   = getDB();
        $stmt = $db->prepare('SELECT stock FROM Producto WHERE id_producto = ? AND estado = 1');
        $stmt->execute([$id_producto]);
        $prod = $stmt->fetch();

        if (!$prod || $prod['stock'] < $cantidad) {
            setFlash('error', 'Stock insuficiente.');
            header('Location: ' . BASE_URL . 'views/client/catalogo.php');
            exit;
        }

        agregarAlCarrito($id_producto, $cantidad);
        setFlash('success', 'Producto agregado al carrito.');
        header('Location: ' . BASE_URL . 'views/client/carrito.php');
        exit;

    // ------------------------------------------
    // FAVORITO: TOGGLE
    // ------------------------------------------
    case 'toggle_favorito':
        protegerRuta('cliente');
        $id_producto = (int)($_POST['id_producto'] ?? 0);
        $id_usuario  = (int)$_SESSION['id_usuario'];

        $db   = getDB();
        $stmt = $db->prepare('SELECT id_favorito FROM Favorito WHERE id_usuario=? AND id_producto=?');
        $stmt->execute([$id_usuario, $id_producto]);
        $fav = $stmt->fetch();

        if ($fav) {
            $db->prepare('DELETE FROM Favorito WHERE id_favorito=?')->execute([$fav['id_favorito']]);
            $msg = 'Eliminado de favoritos.';
        } else {
            $db->prepare('INSERT INTO Favorito (id_usuario,id_producto) VALUES (?,?)')->execute([$id_usuario,$id_producto]);
            $msg = 'Agregado a favoritos.';
        }

        setFlash('success', $msg);
        $ref = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'views/client/catalogo.php';
        header('Location: ' . $ref);
        exit;

    // ------------------------------------------
    // CONFIRMAR COMPRA (Carrito → Venta)
    // ------------------------------------------
    case 'confirmar_compra':
        protegerRuta('cliente');
        iniciarSesion();
        $carrito      = $_SESSION['carrito'] ?? [];
        $id_usuario   = (int)$_SESSION['id_usuario'];
        $metodosValid = ['Efectivo', 'QR', 'Tarjeta', 'Transferencia'];
        $metodo_pago  = in_array($_POST['metodo_pago'] ?? '', $metodosValid)
                        ? $_POST['metodo_pago']
                        : 'Efectivo';

        if (empty($carrito)) {
            setFlash('error', 'El carrito está vacío.');
            header('Location: ' . BASE_URL . 'views/client/carrito.php');
            exit;
        }

        $db  = getDB();

        // Agregar columna metodo_pago si no existe aún
        try {
            $db->exec("ALTER TABLE Venta ADD COLUMN metodo_pago VARCHAR(50) DEFAULT NULL");
        } catch (PDOException $e) { /* ya existe */ }

        $total = 0.0;
        $items = [];

        foreach ($carrito as $id_prod => $cant) {
            $stmt = $db->prepare('SELECT precio, stock FROM Producto WHERE id_producto=? AND estado=1');
            $stmt->execute([$id_prod]);
            $p = $stmt->fetch();
            if (!$p || $p['stock'] < $cant) {
                setFlash('error', 'Stock insuficiente para uno de los productos.');
                header('Location: ' . BASE_URL . 'views/client/carrito.php');
                exit;
            }
            $sub     = $p['precio'] * $cant;
            $total  += $sub;
            $items[] = ['id' => $id_prod, 'cant' => $cant, 'sub' => $sub];
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare('INSERT INTO Venta (id_usuario,total,estado_venta,metodo_pago) VALUES (?,?,"Pendiente",?)');
            $stmt->execute([$id_usuario, $total, $metodo_pago]);
            $id_venta = (int)$db->lastInsertId();

            foreach ($items as $item) {
                $stmt = $db->prepare('INSERT INTO Detalle_Venta (id_venta,id_producto,cantidad,subtotal) VALUES (?,?,?,?)');
                $stmt->execute([$id_venta, $item['id'], $item['cant'], $item['sub']]);
                $stmt = $db->prepare('UPDATE Producto SET stock = stock - ? WHERE id_producto = ?');
                $stmt->execute([$item['cant'], $item['id']]);
            }

            $db->commit();
            unset($_SESSION['carrito']);
            setFlash('success', "¡Compra realizada! Pedido #$id_venta — Pago: $metodo_pago");
            header('Location: ' . BASE_URL . 'views/client/factura.php?id=' . $id_venta);
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'Error al procesar la compra. Intenta nuevamente.');
            header('Location: ' . BASE_URL . 'views/client/carrito.php');
        }
        exit;

    default:
        header('Location: ' . BASE_URL . 'index.php');
        exit;
}
