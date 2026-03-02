<?php
// ============================================================
// config/routes.php — Definición de todas las rutas
// ============================================================

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

// ----------------------------------------------------------
// Rutas públicas
// ----------------------------------------------------------
$router->get('/', \App\Controllers\HomeController::class . '@index');

// API pública
$router->get('/api/secciones',              \App\Controllers\Api\SeccionesController::class    . '@index');
$router->get('/api/tecnologias',            \App\Controllers\Api\TecnologiasController::class  . '@index');
$router->get('/api/portafolio',             \App\Controllers\Api\PortafolioController::class   . '@index');
$router->get('/api/portafolio/{id}',        \App\Controllers\Api\PortafolioController::class   . '@show');
$router->post('/api/contacto',              \App\Controllers\Api\ContactoController::class     . '@store');
$router->get('/api/configuracion/sociales', \App\Controllers\Api\ConfiguracionController::class . '@sociales');
$router->get('/api/configuracion/tema',     \App\Controllers\Api\ConfiguracionController::class . '@tema');
$router->get('/api/configuracion/marca',    \App\Controllers\Api\ConfiguracionController::class . '@marca');
$router->get('/api/sistema/status',         \App\Controllers\Api\SistemaController::class      . '@status');
$router->get('/api/csrf',                   \App\Controllers\Api\SistemaController::class      . '@csrf');

// ----------------------------------------------------------
// Auth
// ----------------------------------------------------------
$router->get( '/admin/login',  \App\Controllers\Admin\AuthController::class . '@showLogin');
$router->post('/admin/login',  \App\Controllers\Admin\AuthController::class . '@login');
$router->get( '/admin/logout', \App\Controllers\Admin\AuthController::class . '@logout', [AuthMiddleware::class]);

// ----------------------------------------------------------
// Admin (grupo con AuthMiddleware)
// ----------------------------------------------------------
$router->group('/admin', [AuthMiddleware::class], function ($r) {
    // Dashboard
    $r->get('',              \App\Controllers\Admin\DashboardController::class . '@index');
    $r->get('/dashboard',    \App\Controllers\Admin\DashboardController::class . '@index');

    // Modo Seguro / Mantenimiento
    $r->post('/modo-seguro/toggle',        \App\Controllers\Admin\DashboardController::class . '@toggleModoSeguro',    [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/modo-mantenimiento/toggle', \App\Controllers\Admin\DashboardController::class . '@toggleMantenimiento', [\App\Middleware\CsrfMiddleware::class]);

    // Secciones
    $r->get( '/secciones',             \App\Controllers\Admin\SeccionesController::class . '@index');
    $r->get( '/secciones/create',      \App\Controllers\Admin\SeccionesController::class . '@create');
    $r->post('/secciones/store',       \App\Controllers\Admin\SeccionesController::class . '@store',  [\App\Middleware\CsrfMiddleware::class]);
    $r->get( '/secciones/edit/{id}',   \App\Controllers\Admin\SeccionesController::class . '@edit');
    $r->post('/secciones/update/{id}', \App\Controllers\Admin\SeccionesController::class . '@update', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/secciones/delete/{id}', \App\Controllers\Admin\SeccionesController::class . '@delete', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/secciones/toggle/{id}', \App\Controllers\Admin\SeccionesController::class . '@toggleVisible', [\App\Middleware\CsrfMiddleware::class]);

    // Portafolio
    $r->get( '/portafolio',             \App\Controllers\Admin\PortafolioController::class . '@index');
    $r->get( '/portafolio/create',      \App\Controllers\Admin\PortafolioController::class . '@create');
    $r->post('/portafolio/store',       \App\Controllers\Admin\PortafolioController::class . '@store',  [\App\Middleware\CsrfMiddleware::class]);
    $r->get( '/portafolio/edit/{id}',   \App\Controllers\Admin\PortafolioController::class . '@edit');
    $r->post('/portafolio/update/{id}', \App\Controllers\Admin\PortafolioController::class . '@update', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/portafolio/delete/{id}', \App\Controllers\Admin\PortafolioController::class . '@delete', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/portafolio/toggle/{id}', \App\Controllers\Admin\PortafolioController::class . '@toggleVisible', [\App\Middleware\CsrfMiddleware::class]);

    // Servicios
    $r->get( '/servicios',             \App\Controllers\Admin\ServiciosController::class . '@index');
    $r->get( '/servicios/create',      \App\Controllers\Admin\ServiciosController::class . '@create');
    $r->post('/servicios/store',       \App\Controllers\Admin\ServiciosController::class . '@store',  [\App\Middleware\CsrfMiddleware::class]);
    $r->get( '/servicios/edit/{id}',   \App\Controllers\Admin\ServiciosController::class . '@edit');
    $r->post('/servicios/update/{id}', \App\Controllers\Admin\ServiciosController::class . '@update', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/servicios/delete/{id}', \App\Controllers\Admin\ServiciosController::class . '@delete', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/servicios/toggle/{id}', \App\Controllers\Admin\ServiciosController::class . '@toggleVisible', [\App\Middleware\CsrfMiddleware::class]);

    // Tecnologias
    $r->get( '/tecnologias',             \App\Controllers\Admin\TecnologiasController::class . '@index');
    $r->get( '/tecnologias/create',      \App\Controllers\Admin\TecnologiasController::class . '@create');
    $r->post('/tecnologias/store',       \App\Controllers\Admin\TecnologiasController::class . '@store',  [\App\Middleware\CsrfMiddleware::class]);
    $r->get( '/tecnologias/edit/{id}',   \App\Controllers\Admin\TecnologiasController::class . '@edit');
    $r->post('/tecnologias/update/{id}', \App\Controllers\Admin\TecnologiasController::class . '@update', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/tecnologias/delete/{id}', \App\Controllers\Admin\TecnologiasController::class . '@delete', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/tecnologias/toggle/{id}', \App\Controllers\Admin\TecnologiasController::class . '@toggleVisible', [\App\Middleware\CsrfMiddleware::class]);

    // Mensajes
    $r->get( '/mensajes',              \App\Controllers\Admin\MensajesController::class . '@index');
    $r->get( '/mensajes/{id}',         \App\Controllers\Admin\MensajesController::class . '@ver');
    $r->post('/mensajes/delete/{id}',  \App\Controllers\Admin\MensajesController::class . '@delete', [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/mensajes/reply/{id}',   \App\Controllers\Admin\MensajesController::class . '@reply',  [\App\Middleware\CsrfMiddleware::class]);

    // Configuración
    $r->get( '/configuracion',                 \App\Controllers\Admin\ConfiguracionController::class . '@index');
    $r->post('/configuracion/update',          \App\Controllers\Admin\ConfiguracionController::class . '@update',         [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/configuracion/test-email',      \App\Controllers\Admin\ConfiguracionController::class . '@testEmail',      [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/configuracion/test-telegram',   \App\Controllers\Admin\ConfiguracionController::class . '@testTelegram',   [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/configuracion/telegram-chats',  \App\Controllers\Admin\ConfiguracionController::class . '@telegramChats',  [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/configuracion/test-imgbb',      \App\Controllers\Admin\ConfiguracionController::class . '@testImgbb',      [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/configuracion/upload-logo',     \App\Controllers\Admin\ConfiguracionController::class . '@uploadLogo',     [\App\Middleware\CsrfMiddleware::class]);
    $r->post('/configuracion/change-password', \App\Controllers\Admin\ConfiguracionController::class . '@changePassword', [\App\Middleware\CsrfMiddleware::class]);
});
