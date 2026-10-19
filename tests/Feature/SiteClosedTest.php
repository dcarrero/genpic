<?php

namespace Tests\Feature;

use App\Models\ArtisticStyle;
use App\Models\GenerationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cierre del servicio (CLOSED_MODE).
 *
 * Lo que importa no es solo que el funnel se cierre, sino que NO se lleve por
 * delante la recuperacion de retratos ya generados ni las paginas publicas.
 */
class SiteClosedTest extends TestCase
{
    use RefreshDatabase;

    private function close(bool $closed = true): void
    {
        config(['genpic.closing.enabled' => $closed]);
    }

    public function test_funnel_de_creacion_devuelve_410_cuando_esta_cerrado(): void
    {
        $this->close();

        $this->get('/create')->assertStatus(410)->assertSee(__('ui.closing_title'));
    }

    public function test_checkout_devuelve_410_cuando_esta_cerrado(): void
    {
        $this->close();
        $session = GenerationSession::factory()->create();

        $this->get('/checkout/'.$session->uuid)->assertStatus(410);
    }

    public function test_upload_por_ajax_devuelve_json_410(): void
    {
        $this->close();

        $this->postJson('/create/upload', [])
            ->assertStatus(410)
            ->assertJson(['closed' => true]);
    }

    public function test_las_descargas_siguen_accesibles_cuando_esta_cerrado(): void
    {
        $this->close();

        $this->get('/my-downloads')->assertOk();
    }

    public function test_las_paginas_publicas_siguen_accesibles_cuando_esta_cerrado(): void
    {
        $this->close();

        $this->get('/')->assertOk();
        $this->get('/gallery')->assertOk();
        $this->get('/terms')->assertOk();
    }

    public function test_la_pagina_de_cierre_es_publica_en_todos_los_idiomas(): void
    {
        $this->close();

        $this->get('/cierre')->assertOk()->assertSee(__('ui.closing_title'));
        $this->get('/en/closing')->assertOk();
        $this->get('/fr/fermeture')->assertOk();
        $this->get('/pt/encerramento')->assertOk();
        $this->get('/it/chiusura')->assertOk();
    }

    public function test_el_aviso_de_cierre_aparece_en_las_paginas_publicas(): void
    {
        $this->close();

        $this->get('/')->assertSee(__('ui.closing_banner_text'));
    }

    public function test_la_pagina_de_cierre_recomienda_los_otros_proyectos(): void
    {
        $this->close();

        $response = $this->get('/cierre');

        $response->assertSee(__('ui.closing_h_projects'));

        foreach (config('genpic.closing.projects') as $project) {
            $response->assertSee('https://'.$project['domain']);
            $response->assertSee(__('ui.closing_project_'.$project['key']), false);
        }
    }

    public function test_un_plazo_de_descarga_vencido_no_se_anuncia(): void
    {
        $this->close();
        config([
            'genpic.closing.date' => now()->subMonths(2)->toDateString(),
            'genpic.closing.downloads_until' => now()->subMonth()->toDateString(),
        ]);

        $this->get('/cierre')
            ->assertSee(__('ui.closing_downloads_1_nodate'))
            ->assertDontSee(__('ui.closing_downloads_1', ['date' => '']), false);
    }

    public function test_el_plazo_de_descarga_se_deriva_del_ttl_si_no_se_configura(): void
    {
        $this->close();
        config([
            'genpic.closing.date' => now()->toDateString(),
            'genpic.closing.downloads_until' => '',
            'genpic.session_ttl_days' => 14,
        ]);

        $esperada = now()->addDays(14)->locale('es')->isoFormat('LL');

        $this->get('/cierre')->assertSee($esperada);
    }

    public function test_una_fecha_de_cierre_futura_se_cuenta_en_futuro(): void
    {
        $this->close();
        config(['genpic.closing.date' => now()->addWeeks(3)->toDateString()]);

        $fecha = now()->addWeeks(3)->locale('es')->isoFormat('LL');

        $this->get('/cierre')
            ->assertSee(__('ui.closing_lede_future', ['date' => $fecha]))
            ->assertDontSee(__('ui.closing_lede', ['date' => $fecha]));
    }

    public function test_sin_el_flag_el_funnel_sigue_abierto_y_sin_aviso(): void
    {
        $this->close(false);
        ArtisticStyle::factory()->create();

        $this->get('/create')->assertOk();
        $this->get('/')->assertDontSee(__('ui.closing_banner_text'));
    }
}
