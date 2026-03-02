<?php
// ============================================================
// public/index.php — Front Controller único
// ============================================================

ob_start(); // Buffer output to prevent warnings/notices breaking JSON responses

define('APP_ROOT', dirname(__DIR__));

// Autoloader PSR-4 manual
spl_autoload_register(function (string $class): void {
    // Core\Foo → core/Foo.php
    // App\Controllers\... → app/Controllers/...
    $map = [
        'Core\\'  => APP_ROOT . '/core/',
        'App\\'   => APP_ROOT . '/app/',
    ];

    foreach ($map as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require $file;
            }
            return;
        }
    }
});

// Config
require APP_ROOT . '/config/config.php';
require APP_ROOT . '/config/session.php';

use Core\Request;
use Core\Response;
use Core\Router;

// Instanciar infraestructura
$request  = new Request();
$response = new Response();
$response->setSecurityHeaders();

$router = new Router();

// Registrar rutas
require APP_ROOT . '/config/routes.php';

// Dispatch
$router->dispatch($request, $response);
