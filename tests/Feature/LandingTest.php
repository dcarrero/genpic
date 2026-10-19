<?php

namespace Tests\Feature;

use App\Models\ArtisticStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_landing_page_shows_active_styles(): void
    {
        ArtisticStyle::factory()->create(['label_es' => 'Renacimiento', 'active' => true]);
        ArtisticStyle::factory()->inactive()->create(['label_es' => 'Oculto']);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Renacimiento');
        $response->assertDontSee('Oculto');
    }

    public function test_english_landing_loads(): void
    {
        $response = $this->get('/en');
        $response->assertStatus(200);
    }
}
