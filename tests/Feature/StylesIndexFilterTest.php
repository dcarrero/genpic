<?php

namespace Tests\Feature;

use App\Models\ArtisticStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El filtro de /styles vive en datos, no en el DOM.
 */
class StylesIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    /** Un apóstrofo en la etiqueta rompía la expresión Alpine de esa tarjeta. */
    public function test_style_with_apostrophe_does_not_break_the_alpine_expression(): void
    {
        ArtisticStyle::factory()->create([
            'active'    => true,
            'label'     => ['es' => "L'Atelier d'Hiver", 'en' => "L'Atelier"],
            'style_key' => 'atelier',
        ]);

        $html = $this->get(route('styles.index'))->assertStatus(200)->getContent();

        $this->assertStringNotContainsString(
            '.includes(search.toLowerCase())',
            $html,
            'Sigue interpolando el texto de busqueda dentro de la expresion x-show'
        );
        $this->assertStringContainsString('x-show="matches(0)"', $html);
    }

    /** El estado vacio consultaba el DOM: no era reactivo y no aparecia nunca. */
    public function test_empty_state_is_reactive_not_a_dom_query(): void
    {
        ArtisticStyle::factory()->count(2)->create(['active' => true]);

        $html = $this->get(route('styles.index'))->assertStatus(200)->getContent();

        $this->assertStringNotContainsString(
            'querySelectorAll',
            $html,
            'El estado vacio sigue consultando el DOM; Alpine no lo re-evalua'
        );
        $this->assertStringContainsString('x-show="visibleCount === 0"', $html);
    }
}
