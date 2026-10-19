<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_loads(): void
    {
        $this->get('/privacy')->assertStatus(200);
    }

    public function test_terms_page_loads(): void
    {
        $this->get('/terms')->assertStatus(200);
    }

    public function test_cookies_page_loads(): void
    {
        $this->get('/cookies')->assertStatus(200);
    }

    public function test_sitemap_returns_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
    }
}
