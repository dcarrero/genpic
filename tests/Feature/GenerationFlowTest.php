<?php

namespace Tests\Feature;

use App\Http\Middleware\GenerationRateLimit;
use App\Jobs\GeneratePortraitJob;
use App\Mail\SessionCreatedMail;
use App\Models\ArtisticStyle;
use App\Models\GenerationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    public function test_upload_requires_photo_and_style(): void
    {
        $this->withoutMiddleware(GenerationRateLimit::class);

        $response = $this->postJson('/create/upload');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photo', 'style_key']);
    }

    public function test_upload_validates_file_is_image(): void
    {
        $this->withoutMiddleware(GenerationRateLimit::class);
        ArtisticStyle::factory()->create(['style_key' => 'renacimiento']);

        $response = $this->postJson('/create/upload', [
            'photo'     => UploadedFile::fake()->create('document.pdf', 100),
            'style_key' => 'renacimiento',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photo']);
    }

    public function test_upload_validates_style_exists(): void
    {
        $this->withoutMiddleware(GenerationRateLimit::class);

        $response = $this->postJson('/create/upload', [
            'photo'     => UploadedFile::fake()->image('photo.jpg', 800, 600),
            'style_key' => 'nonexistent_style',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['style_key']);
    }

    public function test_upload_creates_session_and_dispatches_job(): void
    {
        $this->withoutMiddleware(GenerationRateLimit::class);
        ArtisticStyle::factory()->create(['style_key' => 'renacimiento']);

        $response = $this->postJson('/create/upload', [
            'photo'     => UploadedFile::fake()->image('photo.jpg', 800, 600),
            'style_key' => 'renacimiento',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['session_uuid', 'status']);

        $this->assertDatabaseHas('generation_sessions', [
            'style_key' => 'renacimiento',
            'status'    => 'pending',
        ]);

        Queue::assertPushed(GeneratePortraitJob::class);
    }

    public function test_capture_email_creates_user_and_links_to_session(): void
    {
        Mail::fake();

        $session = GenerationSession::factory()->withoutUser()->create();

        // Usar email con dominio real para pasar validación dns
        $response = $this->postJson('/create/email', [
            'email'        => 'test@gmail.com',
            'session_uuid' => $session->uuid,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('users', ['email' => 'test@gmail.com']);

        $session->refresh();
        $this->assertNotNull($session->user_id);
        $this->assertNotNull($session->email_captured_at);

        Mail::assertSent(SessionCreatedMail::class);
    }

    public function test_capture_email_validates_input(): void
    {
        $response = $this->postJson('/create/email', [
            'email'        => 'not-an-email',
            'session_uuid' => 'not-a-uuid',
        ]);

        $response->assertStatus(422);
    }

    public function test_preview_page_loads_for_valid_session(): void
    {
        $session = GenerationSession::factory()->create();

        $response = $this->get("/create/preview/{$session->uuid}");
        $response->assertStatus(200);
    }

    public function test_status_endpoint_returns_session_state(): void
    {
        $session = GenerationSession::factory()->watermarked()->create();

        $response = $this->getJson("/create/status/{$session->uuid}");
        $response->assertStatus(200);
        $response->assertJson(['session_status' => 'watermarked']);
    }
}
