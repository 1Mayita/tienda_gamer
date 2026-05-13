<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas — AutoZone Admin</title>
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

// Actualizar estado de venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $idVenta    = (int)$_POST['id_venta'];
    $nuevoEstado = sanitizar($_POST['estado_venta']);
    $permitidos  = ['Pendiente','Pagado','Entregado'];
    if (in_array($nuevoEstado, $permitidos)) {
        $s = $db->prepare('UPDATE Venta SET estado_venta=? WHERE id_venta=?');
        $s->execute([$nuevoEstado, $idVenta]);
        setFlash('success', 'Estado actualizado correctamente.');
    }
    header('Location: ventas.php');
    exit;
}

$ventas = $db->query('
    SELECT v.*, u.nombre as cliente, u.correo,
           COUNT(dv.id_detalle) as items
    FROM Venta v
    JOIN Usuario u ON v.id_usuario = u.id_usuario
    LEFT JOIN Detalle_Venta dv ON v.id_venta = dv.id_venta
    GROUP BY v.id_venta
    ORDER BY v.fecha DESC
')->fetchAll();
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Gestión de Ventas</h1>
                <p class="admin-breadcrumb">Admin / Ventas</p>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?> flash-alert mx-4 mt-3">
            <?= htmlspecialchars($flash['mensaje']) ?>
        </div>
        <?php endif; ?>

        <div class="admin-content">
            <div class="admin-card p-0">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Artículos</th>
                                <th>Total</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td class="text-muted">#<?= $v['id_venta'] ?></td>
                                <td>
                                    <p class="fw-600 mb-0" style="color:#fff"><?= htmlspecialchars($v['cliente']) ?></p>
                                    <p class="text-muted small mb-0"><?= htmlspecialchars($v['correo']) ?></p>
                                </td>
                                <td class="text-muted"><?= $v['items'] ?> ítem(s)</td>
                                <td class="fw-600">$<?= number_format($v['total'],2) ?></td>
                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                <td>
                                    <span class="badge-estado <?= strtolower($v['estado_venta']) ?>">
                                        <?= $v['estado_venta'] ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex gap-2 align-items-center">
                                        <input type="hidden" name="id_venta" value="<?= $v['id_venta'] ?>">
                                        <select name="estado_venta" class="auth-input" style="padding:6px 10px;font-size:.8rem;width:130px;">
                                            <option value="Pendiente"  <?= $v['estado_venta']==='Pendiente'  ? 'selected':'' ?>>Pendiente</option>
                                            <option value="Pagado"     <?= $v['estado_venta']==='Pagado'     ? 'selected':'' ?>>Pagado</option>
                                            <option value="Entregado"  <?= $v['estado_venta']==='Entregado'  ? 'selected':'' ?>>Entregado</option>
                                        </select>
                                        <button type="submit" name="actualizar_estado" class="btn-table-action btn-edit" title="Actualizar">✓</button>
                                    </form>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
