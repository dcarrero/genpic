<?php

namespace Tests\Feature;

use App\Models\ArtisticStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StylesTest extends TestCase
{
    use RefreshDatabase;

    public function test_styles_gallery_loads(): void
    {
        ArtisticStyle::factory()->create();

        $response = $this->get('/styles');
        $response->assertStatus(200);
    }

    public function test_individual_style_page_loads(): void
    {
        $style = ArtisticStyle::factory()->create(['style_key' => 'renacimiento']);

        $response = $this->get('/style/renacimiento');
        $response->assertStatus(200);
    }

    public function test_nonexistent_style_returns_404(): void
    {
        $response = $this->get('/style/nonexistent-style');
        $response->assertStatus(404);
    }
}
