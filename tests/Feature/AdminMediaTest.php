<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\GenerationSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Sin restricción por IP (lista vacía = permite todo, ver RestrictAdminByIp)
        config(['genpic.admin_allowed_ips' => []]);

        // Email admin para que canAccessPanel() devuelva true
        config(['genpic.admin_emails' => ['admin@test.com']]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    private function adminUser(): User
    {
        return User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('admin'),
        ]);
    }

    private function sessionWithOriginal(): GenerationSession
    {
        $session = GenerationSession::factory()->create([
            'original_photo_path' => 'originals/test/photo.jpg',
        ]);

        // Crear el archivo en el storage falso
        Storage::disk('local')->put('originals/test/photo.jpg', 'fake-image-content');

        return $session;
    }

    private function generationWithRaw(GenerationSession $session): Generation
    {
        $generation = Generation::factory()->create([
            'generation_session_id' => $session->id,
            'raw_image_path' => 'generations/test/raw.png',
            'watermarked_image_path' => 'watermarked/test/wm.jpg',
        ]);

        Storage::disk('local')->put('generations/test/raw.png', 'fake-raw-content');
        Storage::disk('local')->put('watermarked/test/wm.jpg', 'fake-watermark-content');

        return $generation;
    }

    // ── Test 1: Sin autenticar → 302 redirect, NUNCA la imagen ──────────────────

    public function test_unauthenticated_request_redirects(): void
    {
        $session = $this->sessionWithOriginal();

        $response = $this->get(route('admin.media.original', ['session' => $session->uuid]));

        // Debe redirigir (302) — jamás devolver la imagen directamente
        $response->assertStatus(302);
        $response->assertRedirect();
        // Confirmar que no devuelve ningún contenido de imagen
        $this->assertStringNotContainsString('fake-image', (string) $response->getContent());
    }

    // ── Test 2: Admin autenticado + archivo existente → 200 con cabeceras correctas

    public function test_authenticated_admin_can_stream_original(): void
    {
        $admin = $this->adminUser();
        $session = $this->sessionWithOriginal();

        $response = $this->actingAs($admin)
            ->get(route('admin.media.original', ['session' => $session->uuid]));

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control', ''));

        // Verifica que el streaming manual emite los bytes correctos (sin fpassthru)
        $this->assertSame('fake-image-content', $response->streamedContent());
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function test_authenticated_admin_can_stream_raw_generation(): void
    {
        $admin = $this->adminUser();
        $session = $this->sessionWithOriginal();
        $generation = $this->generationWithRaw($session);

        $response = $this->actingAs($admin)
            ->get(route('admin.media.generation', [
                'generation' => $generation->id,
                'kind' => 'raw',
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control', ''));

        // Verifica que el streaming manual emite los bytes correctos (sin fpassthru)
        $this->assertSame('fake-raw-content', $response->streamedContent());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    // ── Test 3: Archivo purgado (path en BD, archivo ausente en storage) → 404 ──

    public function test_purged_file_returns_404(): void
    {
        $admin = $this->adminUser();
        $session = GenerationSession::factory()->create([
            'original_photo_path' => 'originals/test/purged.jpg',
            // archivo NO creado en Storage::fake → no existe
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.media.original', ['session' => $session->uuid]));

        $response->assertStatus(404);
    }

    // ── Test 4: IP no autorizada → 403 aunque autenticado ────────────────────────

    public function test_unauthorized_ip_returns_403(): void
    {
        // Configurar allowlist con una IP específica
        config(['genpic.admin_allowed_ips' => ['1.2.3.4']]);

        $admin = $this->adminUser();
        $session = $this->sessionWithOriginal();

        // La IP del test no es 1.2.3.4 → RestrictAdminByIp debe bloquear
        $response = $this->actingAs($admin)
            ->get(route('admin.media.original', ['session' => $session->uuid]));

        $response->assertStatus(403);
    }

    // ── Test 5: kind inválido → 404 ──────────────────────────────────────────────

    public function test_invalid_kind_returns_404(): void
    {
        $admin = $this->adminUser();
        $session = $this->sessionWithOriginal();
        $generation = $this->generationWithRaw($session);

        // 'invalid' no está en whereIn(['raw','watermarked']) → 404 de routing
        $response = $this->actingAs($admin)
            ->get(route('admin.media.generation', [
                'generation' => $generation->id,
                'kind' => 'invalid',
            ]));

        $response->assertStatus(404);
    }
}
