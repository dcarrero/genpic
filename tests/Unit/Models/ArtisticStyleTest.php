<?php

namespace Tests\Unit\Models;

use App\Models\ArtisticStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtisticStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_active_only_returns_active_styles(): void
    {
        ArtisticStyle::factory()->create(['active' => true, 'sort_order' => 2]);
        ArtisticStyle::factory()->create(['active' => true, 'sort_order' => 1]);
        ArtisticStyle::factory()->inactive()->create();

        $active = ArtisticStyle::active()->get();
        $this->assertCount(2, $active);
        $this->assertEquals(1, $active->first()->sort_order);
    }

    public function test_has_generations_relationship(): void
    {
        $style = ArtisticStyle::factory()->create();
        $this->assertCount(0, $style->generations);
    }
}
