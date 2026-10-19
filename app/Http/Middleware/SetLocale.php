<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;

class SetLocale
{
    public const DEFAULT = 'es';

    public function handle(Request $request, Closure $next): mixed
    {
        $supported = config('genpic.locales', ['es', 'en']);
        $firstSegment = $request->segment(1);
        $locale = in_array($firstSegment, $supported) ? $firstSegment : self::DEFAULT;

        App::setLocale($locale);
        URL::defaults(['locale' => $locale === self::DEFAULT ? null : $locale]);

        return $next($request);
    }
}
