<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CheckIpBan
{
    public function handle(Request $request, Closure $next): Response
    {
        // Webhooks de Stripe SIEMPRE deben pasar (pagos no se bloquean)
        if ($request->is('webhooks/*')) {
            return $next($request);
        }

        // Admin IPs nunca se banean
        $allowedIps = config('genpic.admin_allowed_ips', []);
        if (! empty($allowedIps) && in_array($request->ip(), $allowedIps, true)) {
            return $next($request);
        }

        // Redis lookup — O(1), sub-millisecond
        try {
            if (Redis::get('ip_ban:'.$request->ip())) {
                // 503 generico — identico a mantenimiento, sin revelar ban
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Service temporarily unavailable.',
                    ], 503);
                }

                return response(view('errors.503'), 503, [
                    'Retry-After' => 3600, // Generico, NO revela duracion real del ban
                ]);
            }
        } catch (\Throwable $e) {
            // Fail-open: si Redis no disponible, no bloquear usuarios
            Log::warning('CheckIpBan Redis lookup failed — fail-open', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }
}
