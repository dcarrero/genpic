<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictAdminByIp
{
    public function handle(Request $request, Closure $next)
    {
        $allowedIps = config('genpic.admin_allowed_ips', []);

        // Si no hay IPs configuradas, permitir todo (dev local)
        if (empty($allowedIps)) {
            return $next($request);
        }

        if (!in_array($request->ip(), $allowedIps)) {
            abort(403, 'Access denied');
        }

        return $next($request);
    }
}
