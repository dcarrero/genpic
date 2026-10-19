<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Añade cabeceras de seguridad HTTP a todas las respuestas.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $this->applyContentSecurityPolicy($response);

        // HSTS: solo en producción con HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * CSP aplicado.
     *
     * **Qué protege `script-src` aquí y qué no.** Lleva 'unsafe-inline' (el banner
     * de cookies, el bootstrap de GA4 y el JSON-LD son inline) y 'unsafe-eval'
     * (Alpine lo necesita, ver abajo). Con ambos, la directiva **no impide
     * inyectar scripts**: lo único que aporta es restringir **desde qué hosts** se
     * puede cargar un <script src>. Es defensa en profundidad, no una barrera de
     * XSS. Para que lo fuera harían falta nonces en cada bloque inline y el build
     * CSP-friendly de Alpine.
     *
     * El panel permite inyectar scripts arbitrarios (`SiteSetting::head_scripts` /
     * `body_scripts`) hacia cualquier host. En vez de romperlos con una allowlist
     * fija, la allowlist se **deriva de lo que el admin ha configurado**. Si esa
     * lectura falla, `script-src` no se aplica: mejor sin directiva que tumbando
     * el sitio en silencio.
     */
    private function applyContentSecurityPolicy(Response $response): void
    {
        // No pisar un CSP que la respuesta ya haya fijado por su cuenta:
        // SecureImageController sirve las imágenes con `default-src 'none'`,
        // mucho más restrictivo que cualquier política de página.
        if ($response->headers->has('Content-Security-Policy')) {
            return;
        }

        $policy = [
            "base-uri 'self'",          // bloquea <base href> inyectado
            "object-src 'none'",        // sin plugins: vector clásico de XSS
            "frame-ancestors 'none'",   // equivalente moderno de X-Frame-Options: DENY
            "form-action 'self'",       // el checkout postea a sí mismo y redirige (302)
            "font-src 'self'",          // fuentes autoalojadas: sin terceros (RGPD)
            "frame-src 'none'",         // Stripe Checkout es redirect, no iframe
        ];

        $scriptHosts = $this->allowedScriptHosts();
        if ($scriptHosts !== null) {
            // 'unsafe-eval' es OBLIGATORIO: el Alpine que trae Livewire evalúa sus
            // expresiones con el truco de AsyncFunction
            // (Object.getPrototypeOf(async function(){}).constructor), equivalente
            // a new Function(). Sin esto Alpine muere al arrancar — y como para
            // entonces ya ha retirado los x-cloak, los desplegables se quedan
            // abiertos, el menú móvil no funciona y la detección facial del
            // uploader no corre. Quitarlo exige el build CSP-friendly de Alpine.
            $policy[] = trim("script-src 'self' 'unsafe-inline' 'unsafe-eval' " . implode(' ', $scriptHosts));
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $policy));
    }

    /**
     * Orígenes permitidos para <script src>.
     *
     * Devuelve null si no se puede determinar con seguridad — en ese caso no se
     * emite `script-src` en absoluto.
     *
     * @return list<string>|null
     */
    private function allowedScriptHosts(): ?array
    {
        try {
            $hosts = ['https://www.googletagmanager.com'];

            // Hosts que el propio admin haya metido por el panel. SiteSetting::get
            // cachea 1h, así que esto no toca BD en cada request.
            foreach (['head_scripts', 'body_scripts'] as $key) {
                $html = SiteSetting::get($key);
                if (! is_string($html) || $html === '') {
                    continue;
                }
                // src="https://host/..." y también //host/... (protocolo relativo)
                preg_match_all('#src\s*=\s*["\'](?:(https?:)?//)([^/"\'\s]+)#i', $html, $m);
                foreach ($m[2] as $host) {
                    $hosts[] = 'https://' . $host;
                }
            }

            return array_values(array_unique($hosts));
        } catch (\Throwable $e) {
            // BD caída, tabla inexistente durante migraciones, etc. Sin allowlist
            // fiable no se emite script-src: romper el sitio es peor que no tenerlo.
            return null;
        }
    }
}
