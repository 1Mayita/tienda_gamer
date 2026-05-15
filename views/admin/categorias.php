<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías — AutoZone Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/landing.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="admin-body">

<?php
require_once '../../config/database.php';
require_once '../../includes/funciones.php';
protegerRuta('admin');
$db    = getDB();
$flash = getFlash();

// CRUD Categorías
$accion = $_POST['accion'] ?? '';
if ($accion === 'crear') {
    $nombre = sanitizar($_POST['nombre_categoria'] ?? '');
    $desc   = sanitizar($_POST['descripcion']      ?? '');
    if ($nombre) {
        $db->prepare('INSERT INTO Categoria (nombre_categoria,descripcion) VALUES (?,?)')->execute([$nombre,$desc]);
        setFlash('success','Categoría creada.');
    }
    header('Location: categorias.php'); exit;
}
if ($accion === 'actualizar') {
    $id     = (int)$_POST['id_categoria'];
    $nombre = sanitizar($_POST['nombre_categoria'] ?? '');
    $desc   = sanitizar($_POST['descripcion']      ?? '');
    $db->prepare('UPDATE Categoria SET nombre_categoria=?,descripcion=? WHERE id_categoria=?')->execute([$nombre,$desc,$id]);
    setFlash('success','Categoría actualizada.');
    header('Location: categorias.php'); exit;
}
if ($accion === 'eliminar') {
    $id = (int)$_POST['id_categoria'];
    try {
        $db->prepare('DELETE FROM Categoria WHERE id_categoria=?')->execute([$id]);
        setFlash('success','Categoría eliminada.');
    } catch (\PDOException $e) {
        setFlash('error','No se puede eliminar: tiene productos asociados.');
    }
    header('Location: categorias.php'); exit;
}

$categorias = $db->query('SELECT c.*, COUNT(p.id_producto) as total_productos FROM Categoria c LEFT JOIN Producto p ON c.id_categoria=p.id_categoria GROUP BY c.id_categoria ORDER BY c.id_categoria')->fetchAll();
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Gestión de Categorías</h1>
                <p class="admin-breadcrumb">Admin / Categorías</p>
            </div>
            <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalCrear">+ Nueva Categoría</button>
        </div>


        <div class="admin-content">
            <div class="row g-4">
                <?php foreach ($categorias as $cat): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 style="color:#fff;font-weight:600;font-size:1rem;"><?= htmlspecialchars($cat['nombre_categoria']) ?></h3>
                                <p style="color:#9899aa;font-size:.82rem"><?= htmlspecialchars($cat['descripcion'] ?: 'Sin descripción') ?></p>
                            </div>
                            <span class="badge-estado pagado"><?= $cat['total_productos'] ?> autos</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#modalEditar<?= $cat['id_categoria'] ?>">
                                ✏️ Editar
                            </button>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_categoria" value="<?= $cat['id_categoria'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                        data-confirm="¿Eliminar esta categoría?">🗑️ Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Editar -->
                <div class="modal fade" id="modalEditar<?= $cat['id_categoria'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content modal-dark">
                            <div class="modal-header modal-header-dark">
                                <h5 class="modal-title">Editar Categoría</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="accion" value="actualizar">
                                <input type="hidden" name="id_categoria" value="<?= $cat['id_categoria'] ?>">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="auth-label">Nombre *</label>
                                        <input type="text" name="nombre_categoria" class="auth-input" value="<?= htmlspecialchars($cat['nombre_categoria']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="auth-label">Descripción</label>
                                        <textarea name="descripcion" class="auth-input" rows="3"><?= htmlspecialchars($cat['descripcion']) ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer modal-footer-dark">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-accent">Actualizar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title">Nueva Categoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="auth-label">Nombre de la Categoría *</label>
                        <input type="text" name="nombre_categoria" class="auth-input" required placeholder="ej. Híbridos">
                    </div>
                    <div class="mb-3">
                        <label class="auth-label">Descripción</label>
                        <textarea name="descripcion" class="auth-input" rows="3" placeholder="Descripción breve..."></textarea>
                    </div>
                </div>
                <div class="modal-footer modal-footer-dark">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-accent">Crear Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/toasts.js"></script>
<?php include '../../includes/flash_toast.php'; ?>
<script src="../../assets/js/main.js"></script>
</body>
</html>
