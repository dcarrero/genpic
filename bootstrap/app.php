<?php

use App\Http\Middleware\CachePublicPages;
use App\Http\Middleware\CheckIpBan;
use App\Http\Middleware\GenerationRateLimit;
use App\Http\Middleware\MaintenanceByEnv;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SiteClosed;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Cloudflare: confiar solo en sus rangos publicados — no spoofeable.
        // IMPORTANTE: este closure corre ANTES de cargar la config/.env (al resolver
        // el kernel HTTP), así que NO se puede usar config() ni env() aquí — la lista
        // va como literal. Fuente: https://www.cloudflare.com/ips/ (+ loopback).
        $middleware->trustProxies(at: [
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
            '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
            '127.0.0.1', '::1',
        ], headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO
        );

        // Security headers en todas las respuestas
        $middleware->prepend(SecurityHeaders::class);

        // Ban de IP por contenido prohibido — antes de mantenimiento
        // (si esta baneado, no importa si hay mantenimiento o no)
        $middleware->prepend(CheckIpBan::class);

        // Mantenimiento por .env — antes de todo, excepto IPs admin
        $middleware->prepend(MaintenanceByEnv::class);

        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
        ]);

        // Trusted hosts: solo aceptar requests con Host válido. Laravel añade
        // por su cuenta el host de APP_URL y sus subdominios (subdomains: true).
        $middleware->trustHosts(at: [
            'localhost',
        ]);

        // Cierre del servicio (CLOSED_MODE) — necesita la ruta ya resuelta, asi
        // que va en el grupo web y no en prepend().
        $middleware->web(append: [
            SiteClosed::class,
        ]);

        $middleware->alias([
            'generation.limit' => GenerationRateLimit::class,
            'set-locale' => SetLocale::class,
            'cache.page' => CachePublicPages::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
