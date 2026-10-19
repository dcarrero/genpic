<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ── Pre-boot: si no hay .env o APP_KEY, mostrar página de mantenimiento ──
$envFile = __DIR__.'/../.env';
if (!file_exists($envFile) || !preg_match('/^APP_KEY=base64:.+/m', file_get_contents($envFile))) {
    http_response_code(503);
    header('Retry-After: 3600');
    readfile(__DIR__.'/maintenance.html');
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
