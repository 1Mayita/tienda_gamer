<?php // Sidebar Admin - AutoZone ?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <a href="../../index.php">
            <span class="brand-icon">⬡</span> AUTO<span class="brand-accent">ZONE</span>
        </a>
        <span class="sidebar-role">Admin Panel</span>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php"  class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php'  ? 'active' : '' ?>">
            <span class="sidebar-icon">📊</span> Dashboard
        </a>
        <a href="productos.php"  class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'productos.php'  ? 'active' : '' ?>">
            <span class="sidebar-icon">🚗</span> Vehículos
        </a>
        <a href="categorias.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'categorias.php' ? 'active' : '' ?>">
            <span class="sidebar-icon">📂</span> Categorías
        </a>
        <a href="ventas.php"     class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'ventas.php'     ? 'active' : '' ?>">
            <span class="sidebar-icon">💰</span> Ventas
        </a>
        <a href="usuarios.php"   class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'usuarios.php'   ? 'active' : '' ?>">
            <span class="sidebar-icon">👥</span> Usuarios
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION['nombre'] ?? 'A', 0, 1)) ?></div>
            <div>
                <p class="sidebar-username"><?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></p>
                <p class="sidebar-email"><?= htmlspecialchars($_SESSION['correo'] ?? '') ?></p>
            </div>
        </div>
        <a href="../../controllers/AuthController.php?accion=logout" class="sidebar-logout">
            🚪 Cerrar Sesión
        </a>
    </div>
</aside>
