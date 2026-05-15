<?php
// ============================================
//  AUTOZONE - Controlador Newsletter
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$correo = trim($_POST['correo'] ?? '');

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Correo electrónico inválido.']);
    exit;
}

$enviado = enviarCorreoNewsletter($correo);

if ($enviado) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'No se pudo enviar el correo. Intenta de nuevo.']);
}
