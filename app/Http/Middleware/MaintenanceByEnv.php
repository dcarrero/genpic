<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Modo mantenimiento controlado por .env (MAINTENANCE_MODE=true)
 *
 * 503 para todos excepto:
 * - IPs en ADMIN_ALLOWED_IPS
 * - Webhooks de Stripe
 */
class MaintenanceByEnv
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('genpic.maintenance_mode')) {
            return $next($request);
        }

        // Permitir acceso al webhook de Stripe siempre
        if ($request->is('webhooks/*')) {
            return $next($request);
        }

        // Comprobar si la IP está autorizada
        $allowedIps = config('genpic.admin_allowed_ips', []);
        $clientIp = $request->ip();

        // Log temporal para debug Cloudflare
        \Log::debug('Maintenance IP check', [
            'ip' => $clientIp,
            'cf_ip' => $request->header('CF-Connecting-IP'),
            'x_forwarded' => $request->header('X-Forwarded-For'),
            'allowed' => $allowedIps,
            'path' => $request->path(),
        ]);

        if (!empty($allowedIps) && in_array($clientIp, $allowedIps, true)) {
            return $next($request);
        }

        return response(file_get_contents(public_path('maintenance.html')), 503, [
            'Retry-After' => 3600,
        ]);
    }
}
