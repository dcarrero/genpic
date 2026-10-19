<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * Middleware para rutas fuera del panel Filament que requieren acceso de admin.
 *
 * Verifica que el usuario esté autenticado y tenga canAccessPanel() del panel 'admin'
 * (comprueba admin_emails). Redirige al login del panel si no hay sesión; 403 si no es admin.
 *
 * Nota: no usamos Filament\Http\Middleware\Authenticate aquí porque fuera del panel
 * no hay panel context (getCurrentPanel() devuelve null) y canAccessPanel(null) falla.
 */
class RequireAdminAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Sin sesión → redirigir al login del panel admin
        if (! $request->user()) {
            $adminPath = config('genpic.admin_path', 'admin');
            $loginUrl = url("/{$adminPath}/login");

            return redirect()->guest($loginUrl);
        }

        // Verificar que el usuario esté en la lista de admin_emails
        $panel = Filament::getPanel('admin');

        if (! $request->user()->canAccessPanel($panel)) {
            abort(403, 'Acceso denegado');
        }

        return $next($request);
    }
}
