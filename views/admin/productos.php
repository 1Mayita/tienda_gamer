<?php
require_once '../../config/database.php';
require_once '../../includes/funciones.php';
protegerRuta('admin');
$db    = getDB();
$flash = getFlash();

$productos  = $db->query('
    SELECT p.*, c.nombre_categoria
    FROM Producto p
    JOIN Categoria c ON p.id_categoria = c.id_categoria
    ORDER BY p.id_producto DESC
')->fetchAll();
$categorias = $db->query('SELECT * FROM Categoria ORDER BY nombre_categoria')->fetchAll();
?>
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
    <style>
        .img-drop-zone {
            border: 2px dashed rgba(255,255,255,.12); border-radius: 10px;
            padding: 18px; text-align: center; cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .img-drop-zone:hover, .img-drop-zone.dragover {
            border-color: #e8272b; background: rgba(232,39,43,.05);
        }
        .img-drop-zone p { margin: 0; color: #666; font-size: .82rem; }
        .img-drop-zone span { color: #e8272b; font-weight: 600; }
        .img-preview-grid { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
        .img-preview-item {
            position:relative; width:76px; height:76px; border-radius:8px;
            border:2px solid rgba(255,255,255,.08); overflow:hidden; cursor:pointer;
            transition:border-color .2s;
        }
        .img-preview-item img { width:100%; height:100%; object-fit:cover; }
        .img-preview-item.principal { border-color:#e8272b; box-shadow:0 0 0 2px rgba(232,39,43,.35); }
        .img-preview-item .pbadge {
            position:absolute; bottom:0; left:0; right:0;
            background:rgba(232,39,43,.85); color:#fff;
            font-size:.55rem; font-weight:700; text-align:center;
            padding:2px; text-transform:uppercase; display:none;
        }
        .img-preview-item.principal .pbadge { display:block; }
        .img-preview-item .xbtn {
            position:absolute; top:2px; right:2px; width:17px; height:17px;
            border-radius:50%; background:rgba(0,0,0,.75); border:none;
            color:#fff; font-size:.6rem; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            opacity:0; transition:opacity .2s;
        }
        .img-preview-item:hover .xbtn { opacity:1; }
        .btn-reactivar {
            background:rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.35);
            color:#4ade80;
        }
        .btn-reactivar:hover { background:rgba(34,197,94,.25); }
        .btn-view {
            background:rgba(99,179,237,.12); border:1px solid rgba(99,179,237,.35);
            color:#63b3ed;
        }
        .btn-view:hover { background:rgba(99,179,237,.25); }
        .ver-imagen-wrap { position:relative; display:inline-block; }
        .ver-imagen-wrap img { width:100%; max-height:260px; object-fit:cover;
            border-radius:12px; border:1px solid rgba(255,255,255,.08); display:block; }
        .ver-detail-row { display:flex; justify-content:space-between; align-items:center;
            padding:9px 0; border-bottom:1px solid rgba(255,255,255,.05); }
        .ver-detail-row:last-child { border-bottom:none; }
        .ver-detail-label { color:#9899aa; font-size:.82rem; }
        .ver-detail-value { color:#fff; font-weight:600; font-size:.9rem; }
    </style>
</head>
<body class="admin-body">

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

        <div class="admin-content">
            <div class="d-flex gap-3 mb-4">
                <input type="text" id="buscador" class="search-input flex-grow-1"
                       placeholder="Buscar por nombre, marca o categoría...">
            </div>

            <div class="admin-card p-0">
                <div class="table-responsive">
                    <table class="admin-table" id="tablaProductos">
                        <thead>
                            <tr>
                                <th>ID</th><th>Imagen</th><th>Vehículo</th><th>Marca</th>
                                <th>Categoría</th><th>Precio</th><th>Stock</th>
                                <th>Estado</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($productos as $p):
                            $prodData = htmlspecialchars(json_encode([
                                'id'              => $p['id_producto'],
                                'nombre'          => $p['nombre'],
                                'marca'           => $p['marca'],
                                'nombre_categoria'=> $p['nombre_categoria'],
                                'id_categoria'    => $p['id_categoria'],
                                'precio'          => $p['precio'],
                                'stock'           => $p['stock'],
                                'descripcion'     => $p['descripcion'],
                                'estado'          => (int)$p['estado'],
                                'imagen'          => $p['imagen'],
                            ]), ENT_QUOTES);
                        ?>
                            <tr data-buscar="<?= strtolower($p['nombre'].' '.$p['marca'].' '.$p['nombre_categoria']) ?>">
                                <td style="color:#9899aa">#<?= $p['id_producto'] ?></td>
                                <td>
                                    <img src="<?= IMG_PRODUCTOS_URL . htmlspecialchars($p['imagen']) ?>"
                                         onerror="this.onerror=null;this.src='<?= IMG_DEFAULT_URL ?>'"
                                         class="prod-thumb" alt="">
                                </td>
                                <td class="fw-600" style="color:#fff"><?= htmlspecialchars($p['nombre']) ?></td>
                                <td style="color:#ccc"><?= htmlspecialchars($p['marca']) ?></td>
                                <td style="color:#ccc"><?= htmlspecialchars($p['nombre_categoria']) ?></td>
                                <td class="fw-600" style="color:#fff">$<?= number_format($p['precio'], 2) ?></td>
                                <td>
                                    <?php
                                    $sc = $p['stock'] == 0 ? 'color:#e8272b;font-weight:700'
                                        : ($p['stock'] <= 3 ? 'color:#f59e0b;font-weight:700'
                                        : 'color:#4ade80;font-weight:600');
                                    ?>
                                    <span style="<?= $sc ?>"><?= $p['stock'] ?> uds.</span>
                                </td>
                                <td>
                                    <span class="badge-estado <?= $p['estado'] ? 'pagado' : 'pendiente' ?>">
                                        <?= $p['estado'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <!-- VER DETALLES -->
                                        <button type="button"
                                                class="btn-table-action btn-view"
                                                title="Ver detalles"
                                                onclick="abrirVer(<?= $prodData ?>)">👁️</button>

                                        <!-- EDITAR -->
                                        <button type="button"
                                                class="btn-table-action btn-edit"
                                                title="Editar vehículo"
                                                onclick="abrirEditar(<?= $prodData ?>)">✏️</button>

                                        <!-- DESACTIVAR (solo si activo) -->
                                        <?php if ($p['estado']): ?>
                                        <form method="POST" action="../../controllers/ProductoController.php"
                                              class="d-inline"
                                              onsubmit="return confirmarAccion(event,'¿Desactivar «<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>»? No aparecerá en el catálogo.')">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                            <button type="submit" class="btn-table-action btn-delete"
                                                    title="Desactivar vehículo">🗑️</button>
                                        </form>
                                        <?php else: ?>
                                        <!-- REACTIVAR (solo si inactivo) -->
                                        <form method="POST" action="../../controllers/ProductoController.php"
                                              class="d-inline"
                                              onsubmit="return confirmarAccion(event,'¿Reactivar «<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>»? Volverá a aparecer en el catálogo.')">
                                            <input type="hidden" name="accion" value="reactivar">
                                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                            <button type="submit"
                                                    class="btn-table-action btn-reactivar"
                                                    title="Reactivar vehículo">✓</button>
                                        </form>
                                        <?php endif; ?>
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

<!-- ══════════════════════════════════════════
     MODAL VER DETALLES
══════════════════════════════════════════ -->
<div class="modal fade" id="modalVer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="verTitulo">👁️ Detalle del Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem">
                <div class="row g-4">
                    <!-- COLUMNA IMAGEN -->
                    <div class="col-md-5">
                        <div class="ver-imagen-wrap mb-3">
                            <img id="verImagen" src="" alt="Imagen del vehículo">
                        </div>
                        <!-- Eliminar foto -->
                        <form method="POST" action="../../controllers/ProductoController.php"
                              id="formEliminarFoto"
                              onsubmit="return confirmarAccion(event,'¿Eliminar la foto de este vehículo? Se usará la imagen por defecto.')">
                            <input type="hidden" name="accion" value="eliminar_imagen">
                            <input type="hidden" name="id_producto" id="verIdFoto">
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                                    id="btnEliminarFoto">
                                🗑️ Eliminar foto
                            </button>
                        </form>
                    </div>

                    <!-- COLUMNA DETALLES -->
                    <div class="col-md-7">
                        <div class="ver-detail-row">
                            <span class="ver-detail-label">ID</span>
                            <span class="ver-detail-value" id="verId" style="color:#9899aa"></span>
                        </div>
                        <div class="ver-detail-row">
                            <span class="ver-detail-label">Nombre</span>
                            <span class="ver-detail-value" id="verNombre"></span>
                        </div>
                        <div class="ver-detail-row">
                            <span class="ver-detail-label">Marca</span>
                            <span class="ver-detail-value" id="verMarca"></span>
                        </div>
                        <div class="ver-detail-row">
                            <span class="ver-detail-label">Categoría</span>
                            <span class="ver-detail-value" id="verCategoria"></span>
                        </div>
                        <div class="ver-detail-row">
                            <span class="ver-detail-label">Precio</span>
                            <span class="ver-detail-value" id="verPrecio" style="color:#4ade80"></span>
                        </div>
                        <div class="ver-detail-row">
                            <span class="ver-detail-label">Stock</span>
                            <span class="ver-detail-value" id="verStock"></span>
                        </div>
                        <div class="ver-detail-row">
                            <span class="ver-detail-label">Estado</span>
                            <span id="verEstado"></span>
                        </div>
                        <div class="ver-detail-row" style="flex-direction:column;align-items:flex-start;gap:6px">
                            <span class="ver-detail-label">Descripción</span>
                            <p id="verDescripcion" style="color:#ccc;font-size:.88rem;margin:0;line-height:1.55"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-dark">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-accent" id="btnVerEditar">✏️ Editar Vehículo</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL CREAR
══════════════════════════════════════════ -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title">➕ Agregar Nuevo Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCrear" method="POST" action="../../controllers/ProductoController.php"
                  enctype="multipart/form-data" novalidate>
                <input type="hidden" name="accion" value="crear">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="auth-label">Nombre del Vehículo *</label>
                            <input type="text" name="nombre" id="cNombre" class="auth-input"
                                   placeholder="ej. Model S Plaid">
                            <span class="field-error">El nombre es obligatorio.</span>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Marca *</label>
                            <input type="text" name="marca" id="cMarca" class="auth-input"
                                   placeholder="ej. Tesla">
                            <span class="field-error">La marca es obligatoria.</span>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Categoría *</label>
                            <select name="id_categoria" id="cCategoria" class="auth-input">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-error">Selecciona una categoría.</span>
                        </div>
                        <div class="col-md-3">
                            <label class="auth-label">Precio (USD) *</label>
                            <input type="number" name="precio" id="cPrecio" class="auth-input"
                                   step="0.01" min="1" placeholder="50000">
                            <span class="field-error">Precio inválido.</span>
                        </div>
                        <div class="col-md-3">
                            <label class="auth-label">Stock *</label>
                            <input type="number" name="stock" id="cStock" class="auth-input"
                                   min="0" value="1">
                            <span class="field-error">Ingresa el stock.</span>
                        </div>
                        <div class="col-12">
                            <label class="auth-label">Descripción</label>
                            <textarea name="descripcion" class="auth-input" rows="3"
                                      placeholder="Descripción del vehículo..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Estado</label>
                            <select name="estado" class="auth-input">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="auth-label">Imágenes
                                <small style="color:#9899aa;font-weight:400">
                                    JPG / PNG / WEBP · máx. 5 MB · haz clic en una para elegir la principal
                                </small>
                            </label>
                            <div class="img-drop-zone" id="dropZoneCrear"
                                 onclick="document.getElementById('imgInputCrear').click()">
                                <p style="font-size:1.5rem;margin-bottom:4px">🖼️</p>
                                <p><span>Clic o arrastra imágenes aquí</span></p>
                            </div>
                            <input type="file" name="imagen" id="imgInputCrear"
                                   accept="image/jpeg,image/png,image/webp" multiple style="display:none">
                            <div class="img-preview-grid" id="previewGridCrear"></div>
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

<!-- ══════════════════════════════════════════
     MODAL EDITAR (se llena por JS)
══════════════════════════════════════════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title" id="editarTitulo">✏️ Editar Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditar" method="POST" action="../../controllers/ProductoController.php"
                  enctype="multipart/form-data" novalidate>
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id_producto" id="eId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="auth-label">Nombre *</label>
                            <input type="text" name="nombre" id="eNombre" class="auth-input">
                            <span class="field-error">El nombre es obligatorio.</span>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Marca *</label>
                            <input type="text" name="marca" id="eMarca" class="auth-input">
                            <span class="field-error">La marca es obligatoria.</span>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Categoría *</label>
                            <select name="id_categoria" id="eCategoria" class="auth-input">
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="auth-label">Precio *</label>
                            <input type="number" name="precio" id="ePrecio" class="auth-input" step="0.01" min="1">
                            <span class="field-error">Precio inválido.</span>
                        </div>
                        <div class="col-md-3">
                            <label class="auth-label">Stock</label>
                            <input type="number" name="stock" id="eStock" class="auth-input" min="0">
                        </div>
                        <div class="col-12">
                            <label class="auth-label">Descripción</label>
                            <textarea name="descripcion" id="eDescripcion" class="auth-input" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="auth-label">Estado</label>
                            <select name="estado" id="eEstado" class="auth-input">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="auth-label">Imagen actual</label>
                            <div class="mb-2">
                                <img id="eImgActual" src="" alt="Imagen actual"
                                     style="max-height:90px;border-radius:8px;border:1px solid #2a2a3a;object-fit:cover;">
                            </div>
                            <label class="auth-label">Nueva imagen
                                <small style="color:#9899aa;font-weight:400">
                                    (dejar vacío para conservar la actual)
                                </small>
                            </label>
                            <div class="img-drop-zone" id="dropZoneEditar"
                                 onclick="document.getElementById('imgInputEditar').click()">
                                <p style="font-size:1.3rem;margin-bottom:3px">🖼️</p>
                                <p><span>Clic o arrastra nuevas imágenes</span></p>
                            </div>
                            <input type="file" name="imagen" id="imgInputEditar"
                                   accept="image/jpeg,image/png,image/webp" multiple style="display:none">
                            <div class="img-preview-grid" id="previewGridEditar"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-dark">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-accent">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- URL base para imágenes -->
<script>const IMG_URL = <?= json_encode(IMG_PRODUCTOS_URL) ?>; const IMG_DEF = <?= json_encode(IMG_DEFAULT_URL) ?>;</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/toasts.js"></script>
<?php include '../../includes/flash_toast.php'; ?>
<script>
/* ─── Buscador ─────────────────────────────── */
document.getElementById('buscador')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaProductos tbody tr').forEach(function(row) {
        row.style.display = (row.dataset.buscar || '').includes(q) ? '' : 'none';
    });
});

/* ─── Abrir modal VER con datos del producto ─── */
var _verData = null;
function abrirVer(p) {
    _verData = p;
    document.getElementById('verTitulo').textContent   = '👁️ ' + p.nombre;
    document.getElementById('verId').textContent       = '#' + p.id;
    document.getElementById('verNombre').textContent   = p.nombre;
    document.getElementById('verMarca').textContent    = p.marca;
    document.getElementById('verCategoria').textContent= p.nombre_categoria || '—';
    document.getElementById('verPrecio').textContent   = '$' + parseFloat(p.precio).toLocaleString('en-US', {minimumFractionDigits:2});
    document.getElementById('verIdFoto').value         = p.id;

    // Stock con color
    var stockEl = document.getElementById('verStock');
    var stockVal = parseInt(p.stock);
    stockEl.textContent = stockVal + ' unidad' + (stockVal !== 1 ? 'es' : '');
    stockEl.style.color = stockVal === 0 ? '#e8272b' : (stockVal <= 3 ? '#f59e0b' : '#4ade80');

    // Estado badge
    var estadoEl = document.getElementById('verEstado');
    estadoEl.innerHTML = p.estado
        ? '<span class="badge-estado pagado">Activo</span>'
        : '<span class="badge-estado pendiente">Inactivo</span>';

    // Descripción
    var desc = p.descripcion || '';
    document.getElementById('verDescripcion').textContent = desc.trim() !== '' ? desc : 'Sin descripción.';

    // Imagen
    var img = document.getElementById('verImagen');
    img.src = IMG_URL + p.imagen;
    img.onerror = function() { this.onerror=null; this.src=IMG_DEF; };

    // Mostrar/ocultar botón eliminar foto si es la imagen por defecto
    var esDefault = (p.imagen === 'default.svg' || p.imagen === 'default.jpg' || !p.imagen);
    document.getElementById('btnEliminarFoto').disabled = esDefault;
    document.getElementById('btnEliminarFoto').title = esDefault ? 'No hay foto personalizada' : '';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVer')).show();
}

// Botón "Editar Vehículo" dentro del modal Ver
document.getElementById('btnVerEditar')?.addEventListener('click', function() {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVer')).hide();
    if (_verData) setTimeout(function() { abrirEditar(_verData); }, 350);
});

/* ─── Abrir modal EDITAR con datos del producto ─ */
function abrirEditar(p) {
    document.getElementById('eId').value            = p.id;
    document.getElementById('eNombre').value        = p.nombre;
    document.getElementById('eMarca').value         = p.marca;
    document.getElementById('ePrecio').value        = p.precio;
    document.getElementById('eStock').value         = p.stock;
    document.getElementById('eDescripcion').value   = p.descripcion || '';
    document.getElementById('eEstado').value        = p.estado;
    document.getElementById('editarTitulo').textContent = '✏️ Editar — ' + p.nombre;

    // Seleccionar categoría correcta
    var sel = document.getElementById('eCategoria');
    for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = (parseInt(sel.options[i].value) === parseInt(p.id_categoria));
    }

    // Mostrar imagen actual
    var img = document.getElementById('eImgActual');
    img.src = IMG_URL + p.imagen;
    img.onerror = function() { this.onerror=null; this.src=IMG_DEF; };

    // Limpiar previews anteriores
    document.getElementById('previewGridEditar').innerHTML = '';
    document.getElementById('imgInputEditar').value = '';

    // Limpiar errores de validación
    document.querySelectorAll('#formEditar .is-invalid').forEach(function(el) {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('#formEditar .field-error.show').forEach(function(el) {
        el.classList.remove('show');
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditar')).show();
}

/* ─── Confirmación de acciones ─────────────── */
function confirmarAccion(e, msg) {
    if (!confirm(msg)) { e.preventDefault(); return false; }
    return true;
}

/* ═══════════════════════════════════════════
   VALIDACIÓN DE FORMULARIOS
═══════════════════════════════════════════ */
function validarCampo(id, min) {
    var el  = document.getElementById(id);
    var err = el?.nextElementSibling;
    if (!el) return true;
    var val = el.value.trim();
    var ok  = val !== '';
    if (ok && min !== undefined) ok = parseFloat(val) >= min;
    el.classList.toggle('is-invalid', !ok);
    if (err?.classList?.contains('field-error')) err.classList.toggle('show', !ok);
    return ok;
}

document.getElementById('formCrear')?.addEventListener('submit', function(e) {
    var ok = [
        validarCampo('cNombre'), validarCampo('cMarca'),
        validarCampo('cCategoria'), validarCampo('cPrecio', 1),
        validarCampo('cStock', 0),
    ].every(Boolean);
    if (!ok) {
        e.preventDefault();
        mostrarToast('error', 'Campos incompletos', 'Revisa los campos marcados en rojo.');
    }
});

document.getElementById('formEditar')?.addEventListener('submit', function(e) {
    var ok = [
        validarCampo('eNombre'), validarCampo('eMarca'),
        validarCampo('eCategoria'), validarCampo('ePrecio', 1),
    ].every(Boolean);
    if (!ok) {
        e.preventDefault();
        mostrarToast('error', 'Campos incompletos', 'Revisa los campos marcados en rojo.');
    }
});

/* ═══════════════════════════════════════════
   UPLOAD MÚLTIPLE DE IMÁGENES
═══════════════════════════════════════════ */
const TIPOS_OK   = ['image/jpeg','image/jpg','image/png','image/webp'];
const MAX_BYTES  = 5 * 1024 * 1024;

function setupUpload(inputId, gridId, dropId) {
    var input  = document.getElementById(inputId);
    var grid   = document.getElementById(gridId);
    var drop   = document.getElementById(dropId);
    if (!input || !grid) return;
    var archivos = [];

    function validar(f) {
        if (!TIPOS_OK.includes(f.type)) {
            mostrarToast('error','Tipo inválido','"'+f.name+'" no es JPG/PNG/WEBP.');
            return false;
        }
        if (f.size > MAX_BYTES) {
            mostrarToast('error','Archivo muy grande','"'+f.name+'" supera 5 MB ('+
                (f.size/1024/1024).toFixed(1)+' MB).');
            return false;
        }
        return true;
    }

    function sync() {
        var dt = new DataTransfer();
        archivos.forEach(function(f){ dt.items.add(f); });
        input.files = dt.files;
    }

    function render() {
        grid.innerHTML = '';
        archivos.forEach(function(file, idx) {
            var item = document.createElement('div');
            item.className = 'img-preview-item' + (idx===0 ? ' principal' : '');

            var img = document.createElement('img');
            img.src = URL.createObjectURL(file);

            var badge = document.createElement('div');
            badge.className = 'pbadge';
            badge.textContent = 'Principal';

            var xbtn = document.createElement('button');
            xbtn.type = 'button'; xbtn.className = 'xbtn'; xbtn.textContent = '✕';
            xbtn.onclick = function(e) {
                e.stopPropagation();
                archivos.splice(idx,1); sync(); render();
            };

            item.appendChild(img); item.appendChild(badge); item.appendChild(xbtn);
            item.onclick = function() {
                var sel = archivos.splice(idx,1)[0];
                archivos.unshift(sel); sync(); render();
                mostrarToast('info','Principal cambiada','"'+sel.name+'" es ahora la imagen principal.',3000);
            };
            grid.appendChild(item);
        });
    }

    function agregar(files) {
        var n = 0;
        Array.from(files).forEach(function(f) {
            if (validar(f)) { archivos.push(f); n++; }
        });
        sync(); render();
        if (n > 0) mostrarToast('success', n+' imagen'+(n>1?'es':'')+' lista'+(n>1?'s':''),
            'Haz clic en una miniatura para elegirla como principal.', 4000);
    }

    input.addEventListener('change', function() { agregar(this.files); });

    if (drop) {
        drop.addEventListener('dragover', function(e){ e.preventDefault(); this.classList.add('dragover'); });
        drop.addEventListener('dragleave', function(){ this.classList.remove('dragover'); });
        drop.addEventListener('drop', function(e){
            e.preventDefault(); this.classList.remove('dragover');
            agregar(e.dataTransfer.files);
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setupUpload('imgInputCrear', 'previewGridCrear', 'dropZoneCrear');
    setupUpload('imgInputEditar', 'previewGridEditar', 'dropZoneEditar');
});
</script>
</body>
</html>
