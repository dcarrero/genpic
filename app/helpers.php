<?php

if (!function_exists('locale_route')) {
    /**
     * Genera una URL de ruta localizada según el locale actual.
     * locale_route('create.index') → /create (ES) o /en/create (EN) o /fr/create (FR)
     */
    function locale_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return route(locale_route_name($name), $parameters, $absolute);
    }
}

if (!function_exists('locale_route_name')) {
    /**
     * Devuelve el nombre de ruta prefijado con el locale actual.
     * locale_route_name('create.index') → 'create.index' (ES) o 'en.create.index' (EN)
     */
    function locale_route_name(string $name): string
    {
        $locale = app()->getLocale();
        if ($locale !== 'es') {
            $localeName = "{$locale}.{$name}";
            if (\Illuminate\Support\Facades\Route::has($localeName)) {
                return $localeName;
            }
        }
        return $name;
    }
}
