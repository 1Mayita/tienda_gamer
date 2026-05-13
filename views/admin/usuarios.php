<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios — AutoZone Admin</title>
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
$db       = getDB();
$usuarios = $db->query('SELECT u.*, COUNT(v.id_venta) as total_ventas FROM Usuario u LEFT JOIN Venta v ON u.id_usuario=v.id_usuario GROUP BY u.id_usuario ORDER BY u.fecha_registro DESC')->fetchAll();
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Usuarios Registrados</h1>
                <p class="admin-breadcrumb">Admin / Usuarios</p>
            </div>
        </div>
        <div class="admin-content">
            <div class="admin-card p-0">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Ventas</th>
                                <th>Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td class="text-muted">#<?= $u['id_usuario'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="sidebar-avatar" style="width:32px;height:32px;font-size:.8rem;">
                                            <?= strtoupper(substr($u['nombre'],0,1)) ?>
                                        </div>
                                        <span class="fw-600" style="color:#fff"><?= htmlspecialchars($u['nombre']) ?></span>
                                    </div>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($u['correo']) ?></td>
                                <td>
                                    <span class="badge-estado <?= $u['rol'] === 'admin' ? 'entregado' : 'pagado' ?>">
                                        <?= ucfirst($u['rol']) ?>
                                    </span>
                                </td>
                                <td class="text-muted"><?= $u['total_ventas'] ?> pedidos</td>
                                <td class="text-muted"><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
