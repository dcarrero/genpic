<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class GenerationRateLimit
{
    /**
     * Handle an incoming request.
     *
     * Usa la misma clave que PhotoUploader ('generation:{ip}') para que ambos
     * compartan el mismo contador y se cumpla el límite real de 3/IP/24h.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $max = config('genpic.rate_limit.generations_per_ip', 3);
        $decay = config('genpic.rate_limit.period_hours', 24) * 3600;
        $key = 'generation:'.$request->ip();   // misma clave que PhotoUploader

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $horasRestantes = (int) ceil(RateLimiter::availableIn($key) / 3600);

            return response()->json([
                'error' => 'Has alcanzado el límite de generaciones gratuitas. Vuelve en '.$horasRestantes.' hora(s).',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, $decay);

        return $next($request);
    }
}
