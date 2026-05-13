<?php
// ============================================
//  AUTOZONE - Funciones Auxiliares
// ============================================

// Iniciar sesión de forma segura
function iniciarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false, // true en producción con HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// Verificar si el usuario está logueado
function estaLogueado(): bool {
    iniciarSesion();
    return isset($_SESSION['id_usuario']) && isset($_SESSION['2fa_ok']) && $_SESSION['2fa_ok'] === true;
}

// Verificar rol del usuario
function esAdmin(): bool {
    return estaLogueado() && $_SESSION['rol'] === 'admin';
}

// Redirigir con protección de rutas
function protegerRuta(string $rolRequerido = 'cliente'): void {
    if (!estaLogueado()) {
        header('Location: ' . BASE_URL . 'views/auth/login.php');
        exit;
    }
    if ($rolRequerido === 'admin' && !esAdmin()) {
        header('Location: ' . BASE_URL . 'views/client/dashboard.php');
        exit;
    }
}

// Sanitizar entrada del usuario
function sanitizar(string $dato): string {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Generar código 2FA aleatorio de 6 dígitos
function generarCodigo2FA(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Formatear precio en dólares
function formatearPrecio(float $precio): string {
    return '$' . number_format($precio, 2, '.', ',');
}

// Mensaje flash para sesión
function setFlash(string $tipo, string $mensaje): void {
    iniciarSesion();
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash(): ?array {
    iniciarSesion();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Carrito: agregar producto
function agregarAlCarrito(int $idProducto, int $cantidad = 1): void {
    iniciarSesion();
    if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
    if (isset($_SESSION['carrito'][$idProducto])) {
        $_SESSION['carrito'][$idProducto] += $cantidad;
    } else {
        $_SESSION['carrito'][$idProducto] = $cantidad;
    }
}

// Carrito: total de ítems
function totalCarrito(): int {
    iniciarSesion();
    if (!isset($_SESSION['carrito'])) return 0;
    return array_sum($_SESSION['carrito']);
}
