<?php
require_once '../../config/database.php';
require_once '../../includes/funciones.php';
protegerRuta('admin');
$db    = getDB();
$flash = getFlash();
$usuarios = $db->query('
    SELECT u.*, COUNT(v.id_venta) as total_ventas
    FROM Usuario u
    LEFT JOIN Venta v ON u.id_usuario = v.id_usuario
    GROUP BY u.id_usuario
    ORDER BY u.fecha_registro DESC
')->fetchAll();
$totalClientes = count(array_filter($usuarios, fn($u) => $u['rol'] === 'cliente'));
$totalAdmins   = count(array_filter($usuarios, fn($u) => $u['rol'] === 'admin'));
$totalPremium  = count(array_filter($usuarios, fn($u) => $u['rol'] === 'premium'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios — AutoZone Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/landing.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Gestión de Usuarios</h1>
                <p class="admin-breadcrumb">Admin / Usuarios</p>
            </div>
            <button class="btn btn-accent btn-sm px-4"
                    data-bs-toggle="modal" data-bs-target="#modalCrear">
                + Nuevo Usuario
            </button>
        </div>

        <div class="admin-content">


            <!-- STATS -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card stat-blue">
                        <div class="stat-icon">👥</div>
                        <div class="stat-body">
                            <span class="stat-value"><?= count($usuarios) ?></span>
                            <span class="stat-label">Total Usuarios</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-green">
                        <div class="stat-icon">👤</div>
                        <div class="stat-body">
                            <span class="stat-value"><?= $totalClientes ?></span>
                            <span class="stat-label">Clientes</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-gold">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-body">
                            <span class="stat-value"><?= $totalPremium ?></span>
                            <span class="stat-label">Premium</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card stat-red">
                        <div class="stat-icon">🛡️</div>
                        <div class="stat-body">
                            <span class="stat-value"><?= $totalAdmins ?></span>
                            <span class="stat-label">Administradores</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA -->
            <div class="admin-card p-0">
                <!-- Buscador -->
                <div class="p-3 border-bottom" style="border-color:rgba(255,255,255,.06)!important">
                    <input type="text" id="buscarUsuario" class="auth-input"
                           placeholder="🔍  Buscar por nombre, correo o rol..."
                           style="max-width:380px">
                </div>
                <div class="table-responsive">
                    <table class="admin-table" id="tablaUsuarios">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Pedidos</th>
                                <th>Registro</th>
                                <th style="min-width:260px">Gestión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u):
                                $esMismoAdmin = (int)$u['id_usuario'] === (int)$_SESSION['id_usuario'];
                                $badgeClase   = match($u['rol']) {
                                    'admin'   => 'entregado',
                                    'premium' => 'premium',
                                    default   => 'pagado',
                                };
                                $badgeLabel = match($u['rol']) {
                                    'admin'   => '🛡️ Admin',
                                    'premium' => '⭐ Premium',
                                    default   => 'Cliente',
                                };
                                $avatarBg = match($u['rol']) {
                                    'admin'   => '#3b82f6',
                                    'premium' => '#c9a84c',
                                    default   => '#e8272b',
                                };
                            ?>
                            <tr data-search="<?= strtolower($u['nombre'].' '.$u['correo'].' '.$u['rol']) ?>">
                                <td style="color:#9899aa">#<?= $u['id_usuario'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="sidebar-avatar"
                                             style="width:34px;height:34px;font-size:.85rem;background:<?= $avatarBg ?>;flex-shrink:0">
                                            <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <span class="fw-600" style="color:#fff;display:block">
                                                <?= htmlspecialchars($u['nombre']) ?>
                                                <?php if ($esMismoAdmin): ?>
                                                <span style="font-size:.65rem;color:#888;margin-left:3px">(tú)</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td style="color:#9899aa;font-size:.83rem"><?= htmlspecialchars($u['correo']) ?></td>
                                <td><span class="badge-estado <?= $badgeClase ?>"><?= $badgeLabel ?></span></td>
                                <td>
                                    <span style="color:<?= $u['total_ventas'] > 0 ? '#4ade80' : '#9899aa' ?>;font-weight:600">
                                        <?= $u['total_ventas'] ?>
                                    </span>
                                    <span style="color:#666;font-size:.78rem"> pedido<?= $u['total_ventas'] != 1 ? 's' : '' ?></span>
                                </td>
                                <td style="color:#9899aa;font-size:.82rem"><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                                <td>
                                    <?php if (!$esMismoAdmin): ?>
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <!-- CAMBIAR ROL -->
                                        <form method="POST" action="../../controllers/AuthController.php"
                                              class="d-flex gap-1 align-items-center">
                                            <input type="hidden" name="accion" value="cambiar_rol">
                                            <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                            <select name="rol" class="auth-input"
                                                    style="padding:5px 28px 5px 8px;font-size:.76rem;width:115px;">
                                                <option value="cliente"  <?= $u['rol']==='cliente'  ? 'selected':'' ?>>Cliente</option>
                                                <option value="premium"  <?= $u['rol']==='premium'  ? 'selected':'' ?>>⭐ Premium</option>
                                                <option value="admin"    <?= $u['rol']==='admin'    ? 'selected':'' ?>>🛡️ Admin</option>
                                            </select>
                                            <button type="submit" class="btn-table-action btn-edit" title="Guardar rol">✓</button>
                                        </form>
                                        <!-- EDITAR DATOS -->
                                        <button type="button"
                                                class="btn-table-action btn-edit"
                                                title="Editar usuario"
                                                onclick="abrirEditar(<?= htmlspecialchars(json_encode([
                                                    'id'     => $u['id_usuario'],
                                                    'nombre' => $u['nombre'],
                                                    'correo' => $u['correo'],
                                                    'rol'    => $u['rol'],
                                                ]), ENT_QUOTES) ?>)">✏️</button>
                                        <!-- ELIMINAR -->
                                        <form method="POST" action="../../controllers/AuthController.php"
                                              onsubmit="return confirmarEliminar(<?= $u['total_ventas'] ?>, '<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>')">
                                            <input type="hidden" name="accion" value="eliminar_usuario">
                                            <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                            <button type="submit" class="btn-table-action btn-delete" title="Eliminar">🗑️</button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <span style="font-size:.78rem;color:#444">— tu cuenta —</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="d-flex gap-3 mt-3 align-items-center flex-wrap">
                <span style="font-size:.76rem;color:#555">Roles:</span>
                <span class="badge-estado pagado">Cliente — acceso a catálogo y compras</span>
                <span class="badge-estado premium">⭐ Premium — cliente destacado</span>
                <span class="badge-estado entregado">🛡️ Admin — acceso total al panel</span>
            </div>

        </div>
    </main>
</div>

<!-- ============ MODAL CREAR USUARIO ============ -->
<div class="modal fade" id="modalCrear" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title">➕ Nuevo Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem">
                <form method="POST" action="../../controllers/AuthController.php" id="formCrear">
                    <input type="hidden" name="accion" value="crear_usuario">
                    <div class="mb-3">
                        <label class="auth-label">Nombre completo *</label>
                        <input type="text" name="nombre" class="auth-input" placeholder="Ej: Juan Pérez" required>
                    </div>
                    <div class="mb-3">
                        <label class="auth-label">Correo electrónico *</label>
                        <input type="email" name="correo" class="auth-input" placeholder="correo@ejemplo.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="auth-label">Contraseña *</label>
                        <input type="password" name="contrasena" class="auth-input"
                               placeholder="Mínimo 8 caracteres" required minlength="8">
                    </div>
                    <div class="mb-4">
                        <label class="auth-label">Rol *</label>
                        <select name="rol" class="auth-input">
                            <option value="cliente">Cliente</option>
                            <option value="premium">⭐ Premium</option>
                            <option value="admin">🛡️ Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-accent w-100 py-2">Crear Usuario</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL EDITAR USUARIO ============ -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title">✏️ Editar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem">
                <form method="POST" action="../../controllers/AuthController.php">
                    <input type="hidden" name="accion" value="editar_usuario">
                    <input type="hidden" name="id_usuario" id="editId">
                    <div class="mb-3">
                        <label class="auth-label">Nombre completo *</label>
                        <input type="text" name="nombre" id="editNombre" class="auth-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="auth-label">Correo electrónico *</label>
                        <input type="email" name="correo" id="editCorreo" class="auth-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="auth-label">Rol</label>
                        <select name="rol" id="editRol" class="auth-input">
                            <option value="cliente">Cliente</option>
                            <option value="premium">⭐ Premium</option>
                            <option value="admin">🛡️ Admin</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="auth-label">Nueva contraseña <span style="color:#555;font-weight:400">(dejar vacío = sin cambios)</span></label>
                        <input type="password" name="contrasena" class="auth-input"
                               placeholder="Dejar vacío para no cambiar" minlength="8">
                    </div>
                    <button type="submit" class="btn btn-accent w-100 py-2">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/toasts.js"></script>
<?php include '../../includes/flash_toast.php'; ?>
<script>
// Abrir modal editar con datos del usuario
function abrirEditar(u) {
    document.getElementById('editId').value     = u.id;
    document.getElementById('editNombre').value = u.nombre;
    document.getElementById('editCorreo').value = u.correo;
    document.getElementById('editRol').value    = u.rol;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditar')).show();
}

// Confirmar eliminación
function confirmarEliminar(ventas, nombre) {
    if (ventas > 0) {
        alert('No se puede eliminar a "' + nombre + '" porque tiene ' + ventas + ' pedido(s) registrado(s).\n\nPrimero cambia su rol o espera a que no tenga pedidos activos.');
        return false;
    }
    return confirm('¿Eliminar definitivamente a "' + nombre + '"?\nEsta acción no se puede deshacer.');
}

// Búsqueda en tiempo real
document.getElementById('buscarUsuario')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('#tablaUsuarios tbody tr').forEach(function(row) {
        const text = row.dataset.search || '';
        row.style.display = text.includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
