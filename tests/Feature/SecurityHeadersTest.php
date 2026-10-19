<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_ships_the_enforced_csp(): void
    {
        $r = $this->get('/');
        $r->assertStatus(200);
        $csp = $r->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp, 'No hay CSP en una página HTML');
        foreach (["base-uri 'self'", "object-src 'none'", "frame-ancestors 'none'", "form-action 'self'"] as $d) {
            $this->assertStringContainsString($d, $csp, "Falta la directiva: {$d}");
        }
        // script-src ya se aplica: la allowlist se deriva de los scripts que el
        // admin haya configurado, así que no los rompe (ver tests de abajo).
        $this->assertStringContainsString('script-src', $csp);
    }

    public function test_script_src_is_enforced_with_gtm(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
    }

    /**
     * REGRESION (v1.6.1): se aplico script-src sin 'unsafe-eval' y Alpine murio al
     * arrancar. Como para entonces ya habia retirado los x-cloak, los desplegables
     * se quedaron abiertos, el menu movil dejo de funcionar y la deteccion facial
     * del uploader no corria. Livewire empaqueta Alpine, y Alpine evalua sus
     * expresiones con el truco de AsyncFunction — equivalente a new Function().
     *
     * Si algun dia se quita 'unsafe-eval', hay que migrar al build CSP-friendly de
     * Alpine ANTES, no despues.
     */
    public function test_script_src_allows_unsafe_eval_because_alpine_needs_it(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString(
            "'unsafe-eval'",
            $csp,
            'Sin unsafe-eval, Alpine muere al arrancar y toda la interactividad del sitio se rompe'
        );
    }

    /** Alpine viene dentro de Livewire y sigue necesitando evaluacion dinamica. */
    public function test_bundled_alpine_still_requires_dynamic_evaluation(): void
    {
        $js = base_path('vendor/livewire/livewire/dist/livewire.min.js');
        if (! file_exists($js)) {
            $this->markTestSkipped('livewire.min.js no encontrado');
        }

        $src = file_get_contents($js);
        $needsEval = str_contains($src, 'new Function')
            || str_contains($src, 'Object.getPrototypeOf(async function');

        $this->assertTrue(
            $needsEval,
            'Alpine ya no usa evaluacion dinamica: se puede retirar unsafe-eval del CSP'
        );
    }

    public function test_font_src_allows_no_third_parties(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("font-src 'self'", $csp, 'Las fuentes son locales');
        $this->assertStringNotContainsString('gstatic', $csp, 'Quedan restos de Google Fonts en el CSP');
    }

    /**
     * El caso que hacia inaplicable el script-src: el panel permite inyectar
     * scripts de cualquier host. La allowlist debe derivarse de lo configurado,
     * no romperlos.
     */
    public function test_admin_injected_script_host_is_allowlisted(): void
    {
        \App\Models\SiteSetting::updateOrCreate(
            ['key' => 'head_scripts'],
            ['value' => '<script src="https://static.hotjar.com/c/hotjar.js" async></script>']
        );
        \Illuminate\Support\Facades\Cache::forget('site_setting_head_scripts');

        $csp = $this->get('/')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString(
            'https://static.hotjar.com',
            $csp,
            'Un script inyectado desde el panel quedaria bloqueado por el CSP'
        );
    }

    /** Protocolo relativo (//host/x.js) tambien debe reconocerse. */
    public function test_protocol_relative_admin_script_is_allowlisted(): void
    {
        \App\Models\SiteSetting::updateOrCreate(
            ['key' => 'body_scripts'],
            ['value' => '<script src="//cdn.example.org/tag.js"></script>']
        );
        \Illuminate\Support\Facades\Cache::forget('site_setting_body_scripts');

        $csp = $this->get('/')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('https://cdn.example.org', $csp);
    }

    /** El middleware NO debe pisar un CSP más restrictivo puesto por la respuesta. */
    public function test_does_not_clobber_an_existing_csp(): void
    {
        $mw = new \App\Http\Middleware\SecurityHeaders();
        $req = Request::create('/img/x.jpg');

        $res = $mw->handle($req, function () {
            $r = new HttpResponse('binario');
            $r->headers->set('Content-Security-Policy', "default-src 'none'");
            return $r;
        });

        $this->assertSame(
            "default-src 'none'",
            $res->headers->get('Content-Security-Policy'),
            'El middleware pisó el CSP de la respuesta, debilitando las imágenes'
        );
    }
}
