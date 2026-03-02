<?php
// ============================================================
// config/session.php — Configuración segura de sesión
// ============================================================

$secure   = (APP_ENV === 'production');
$lifetime = 7200; // 2 horas

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_name('CDNXSESS');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID si la sesión es nueva o lleva más de 30 min sin regenerar
if (!isset($_SESSION['_last_regen'])) {
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
} elseif (time() - $_SESSION['_last_regen'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
}
