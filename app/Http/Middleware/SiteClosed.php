<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierre del servicio (CLOSED_MODE=true).
 *
 * Corta las altas y las compras sin tumbar la web: el funnel de creacion y el
 * de pago devuelven 410 Gone con la pagina de cierre. El resto del sitio sigue
 * navegable — incluidas /my-downloads, /portrait y las descargas firmadas, para
 * que quien tenga un retrato pendiente pueda recuperarlo.
 *
 * 410 (y no 404 ni 503) es deliberado: le dice a Google que estas URLs se
 * retiran para siempre y las desindexe.
 */
class SiteClosed
{
    /**
     * Rutas cerradas, por nombre y sin prefijo de idioma.
     *
     * Fuera de la lista a proposito:
     * - create.preview / create.status: sesiones ya en vuelo al activar el flag.
     * - checkout.success / checkout.cancel: aterrizaje de un pago ya iniciado.
     * - portrait.* y downloads.*: recuperacion de lo ya generado.
     */
    private const BLOCKED = [
        'create.index',
        'create.upload',
        'create.email',
        'checkout.index',
        'checkout.session',
        'checkout.validate-coupon',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('genpic.closing.enabled')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName === null || ! in_array($this->stripLocale($routeName), self::BLOCKED, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'closed' => true,
                'message' => __('ui.closing_notice'),
            ], 410);
        }

        return response()->view('closed', [], 410);
    }

    /** 'en.create.index' => 'create.index' */
    private function stripLocale(string $routeName): string
    {
        $prefixes = array_diff(config('genpic.locales', ['es']), ['es']);

        if ($prefixes === []) {
            return $routeName;
        }

        return preg_replace('#^('.implode('|', $prefixes).')\.#', '', $routeName);
    }
}
