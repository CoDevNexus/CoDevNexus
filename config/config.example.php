<?php
// ============================================================
// config/config.php — Credenciales de BD y claves de la app
// ============================================================
// OPCIÓN A: Copia este archivo a config.php y edita los valores.
// OPCIÓN B: Ejecuta el instalador web en /install.php (recomendado).
// ============================================================

// ── Base de datos ─────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'codevnexus');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Clave de aplicación ────────────────────────────────────
// Genera 32 bytes aleatorios: php -r "echo bin2hex(random_bytes(16));"
define('APP_KEY', 'REPLACE_WITH_32_BYTE_HEX_KEY');

// ── Entorno ────────────────────────────────────────────────
// 'development' muestra errores | 'production' los oculta
define('APP_ENV', 'production');

// ── URL base (sin trailing slash) ──────────────────────────
define('APP_URL', 'https://yourdomain.com');
