<?php

namespace Tests\Feature;

use App\Models\ArtisticStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El desplegable de idioma y el hreflang salen del mismo calculo.
 *
 * El bug: ambos se resolvian por separado y el desplegable pegaba el prefijo al
 * path actual. Con slugs localizados eso produce URLs que no existen —
 * /fr/cierre en vez de /fr/fermeture — y hasta ahora solo lo tapaban los
 * redirects 301 de las landings.
 */
class LocaleSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_selector_de_idioma_usa_el_slug_de_cada_idioma(): void
    {
        config(['genpic.closing.enabled' => true]);

        $this->get('/cierre')
            ->assertSee('/en/closing')
            ->assertSee('/fr/fermeture')
            ->assertSee('/pt/encerramento')
            ->assertSee('/it/chiusura')
            ->assertDontSee('/fr/cierre')
            ->assertDontSee('/en/cierre');
    }

    public function test_lo_mismo_desde_una_pagina_ya_traducida(): void
    {
        config(['genpic.closing.enabled' => true]);

        $this->get('/fr/fermeture')
            ->assertSee('/it/chiusura')
            ->assertDontSee('/it/fermeture');
    }

    public function test_las_landings_con_slug_localizado_tampoco_se_rompen(): void
    {
        ArtisticStyle::factory()->count(3)->create();

        $response = $this->get('/'.__('ui.lp_slug_professional'));

        $response->assertOk()
            ->assertSee('/fr/'.trans('ui.lp_slug_professional', [], 'fr'))
            ->assertDontSee('/fr/'.trans('ui.lp_slug_professional', [], 'es'));
    }

    public function test_las_urls_alternativas_no_arrastran_parametros_espurios(): void
    {
        config(['genpic.closing.enabled' => true]);

        // Route::view() registra 'view' y 'status' como parametros de ruta y
        // route() los serializa: /fr/fermeture?view=closed&status=200.
        $this->get('/cierre')
            ->assertDontSee('view=closed', false)
            ->assertDontSee('status=200', false);
    }

    public function test_hreflang_y_selector_apuntan_a_lo_mismo(): void
    {
        config(['genpic.closing.enabled' => true]);

        $html = $this->get('/cierre')->getContent();

        preg_match_all('#<link rel="alternate" hreflang="([a-z]+)" href="([^"]+)"#', $html, $m, PREG_SET_ORDER);

        $hreflangs = [];
        foreach ($m as $match) {
            $hreflangs[$match[1]] = $match[2];
        }

        $esperados = [
            'es' => '/cierre',
            'en' => '/en/closing',
            'fr' => '/fr/fermeture',
            'pt' => '/pt/encerramento',
            'it' => '/it/chiusura',
        ];

        foreach ($esperados as $loc => $path) {
            $this->assertArrayHasKey($loc, $hreflangs);
            $this->assertStringContainsString($path, $hreflangs[$loc]);
        }
    }
}
