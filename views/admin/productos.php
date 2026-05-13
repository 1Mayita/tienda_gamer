<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vehículos — AutoZone</title>
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

$productos   = $db->query('SELECT p.*, c.nombre_categoria FROM Producto p JOIN Categoria c ON p.id_categoria = c.id_categoria ORDER BY p.id_producto DESC')->fetchAll();
$categorias  = $db->query('SELECT * FROM Categoria ORDER BY nombre_categoria')->fetchAll();
$abrirModal  = $_GET['modal'] ?? '';
$editId      = (int)($_GET['editar'] ?? 0);
$editProd    = null;
if ($editId > 0) {
    $s = $db->prepare('SELECT * FROM Producto WHERE id_producto = ?');
    $s->execute([$editId]);
    $editProd = $s->fetch();
}
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Gestión de Vehículos</h1>
                <p class="admin-breadcrumb">Admin / Vehículos</p>
            </div>
            <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalCrear">
                + Agregar Vehículo
            </button>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mx-4 mt-3">
            <?= htmlspecialchars($flash['mensaje']) ?>
        </div>
        <?php endif; ?>

        <div class="admin-content">
            <!-- BUSCADOR -->
            <div class="d-flex gap-3 mb-4">
                <input type="text" id="buscador" class="search-input flex-grow-1" placeholder="Buscar vehículo...">
            </div>

            <div class="admin-card p-0">
                <div class="table-responsive">
                    <table class="admin-table" id="tablaProductos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Vehículo</th>
                                <th>Marca</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $p): ?>
                            <tr class="producto-item"
                                data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>"
                                data-marca="<?= strtolower(htmlspecialchars($p['marca'])) ?>">
                                <td class="text-muted">#<?= $p['id_producto'] ?></td>
                                <td>
                                    <img src="../../assets/img/<?= htmlspecialchars($p['imagen']) ?>"
                                         onerror="this.src='../../assets/img/default.jpg'"
                                         class="prod-thumb" alt="">
                                </td>
                                <td class="fw-600"><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><?= htmlspecialchars($p['marca']) ?></td>
                                <td><?= htmlspecialchars($p['nombre_categoria']) ?></td>
                                <td class="fw-600">$<?= number_format($p['precio'], 2) ?></td>
                                <td>
                                    <span class="<?= $p['stock'] <= 3 ? 'text-danger fw-600' : 'text-success' ?>">
                                        <?= $p['stock'] ?> uds.
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-estado <?= $p['estado'] ? 'pagado' : 'pendiente' ?>">
                                        <?= $p['estado'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="productos.php?editar=<?= $p['id_producto'] ?>"
                                           class="btn-table-action btn-edit"
                                           data-bs-toggle="modal" data-bs-target="#modalEditar">✏️</a>
                                        <form method="POST" action="../../controllers/ProductoController.php" class="d-inline">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                            <button type="submit" class="btn-table-action btn-delete"
                                                    data-confirm="¿Desactivar este vehículo?">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL CREAR -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title">Agregar Nuevo Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../../controllers/ProductoController.php" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="auth-label">Nombre del Vehículo *</label>
                            <input type="text" name="nombre" class="auth-input" required placeholder="ej. Model S Plaid">
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Marca *</label>
                            <input type="text" name="marca" class="auth-input" required placeholder="ej. Tesla">
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Categoría *</label>
                            <select name="id_categoria" class="auth-input" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="auth-label">Precio (USD) *</label>
                            <input type="number" name="precio" class="auth-input" step="0.01" min="1" required placeholder="50000">
                        </div>
                        <div class="col-md-3">
                            <label class="auth-label">Stock</label>
                            <input type="number" name="stock" class="auth-input" min="0" value="1" required>
                        </div>
                        <div class="col-12">
                            <label class="auth-label">Descripción</label>
                            <textarea name="descripcion" class="auth-input" rows="3" placeholder="Descripción del vehículo..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Imagen</label>
                            <input type="file" name="imagen" class="auth-input" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Estado</label>
                            <select name="estado" class="auth-input">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-dark">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-accent">Guardar Vehículo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<?php if ($editProd): ?>
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title">Editar Vehículo #<?= $editProd['id_producto'] ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../../controllers/ProductoController.php" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_producto" value="<?= $editProd['id_producto'] ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="auth-label">Nombre *</label>
                            <input type="text" name="nombre" class="auth-input" value="<?= htmlspecialchars($editProd['nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Marca *</label>
                            <input type="text" name="marca" class="auth-input" value="<?= htmlspecialchars($editProd['marca']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Categoría *</label>
                            <select name="id_categoria" class="auth-input" required>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>" <?= $c['id_categoria'] == $editProd['id_categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre_categoria']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="auth-label">Precio *</label>
                            <input type="number" name="precio" class="auth-input" step="0.01" value="<?= $editProd['precio'] ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="auth-label">Stock</label>
                            <input type="number" name="stock" class="auth-input" min="0" value="<?= $editProd['stock'] ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="auth-label">Descripción</label>
                            <textarea name="descripcion" class="auth-input" rows="3"><?= htmlspecialchars($editProd['descripcion']) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Nueva Imagen (opcional)</label>
                            <input type="file" name="imagen" class="auth-input" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Estado</label>
                            <select name="estado" class="auth-input">
                                <option value="1" <?= $editProd['estado'] ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= !$editProd['estado'] ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const m = new bootstrap.Modal(document.getElementById('modalEditar'));
    m.show();
});
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script>
// Buscador para la tabla de admin
document.getElementById('buscador')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaProductos tbody tr').forEach(row => {
        const texto = row.dataset.nombre + ' ' + row.dataset.marca;
        row.style.display = texto.includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
