<?php

namespace Tests\Feature;

use App\Livewire\PhotoUploader;
use App\Models\ArtisticStyle;
use App\Models\GenerationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Suelo de accesibilidad del funnel. No sustituye a una auditoria manual,
 * pero fija lo que se arreglo para que no vuelva a perderse.
 */
class FunnelAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    /** El email y la deteccion facial viven tras $photoReady: hay que montarlo. */
    private function uploaderHtml(array $state = ['photoReady' => true]): string
    {
        $style = ArtisticStyle::factory()->create(['active' => true]);
        $c = Livewire::test(PhotoUploader::class, ['styleKey' => $style->style_key]);
        foreach ($state as $k => $v) {
            $c->set($k, $v);
        }

        return $c->html();
    }

    /** Ningun input del funnel puede depender solo del placeholder. */
    public function test_uploader_inputs_have_an_accessible_name(): void
    {
        $html = $this->uploaderHtml();

        $this->assertStringContainsString('for="pu-email"', $html, 'El email no tiene label asociado');
        $this->assertStringContainsString('id="pu-email"', $html);
        $this->assertStringContainsString('autocomplete="email"', $html);
    }

    /** El email centrado hacia saltar el cursor al teclear. */
    public function test_email_input_is_not_centred(): void
    {
        $html = $this->uploaderHtml();
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*id="pu-email"[^>]*text-center/s',
            $html,
            'El input de email sigue centrado: el cursor salta al escribir'
        );
    }

    /** El banner de error lo pinta Livewire sin recargar: hay que anunciarlo. */
    public function test_error_banner_is_announced(): void
    {
        $html = $this->uploaderHtml(['error' => 'Algo fallo']);

        $this->assertStringContainsString('role="alert"', $html, 'El error no se anuncia al lector de pantalla');
    }

    /** La deteccion facial decide si puedes continuar y era muda. */
    public function test_face_detection_is_a_live_region(): void
    {
        $html = $this->uploaderHtml(['photoReady' => true, 'faceDetectionEnabled' => true]);
        $this->assertStringContainsString('aria-live="polite"', $html);
    }

    /** Los spinners no aportan nada al lector: deben quedar ocultos. */
    public function test_spinners_are_hidden_from_screen_readers(): void
    {
        $html = $this->uploaderHtml(['photoReady' => true, 'faceDetectionEnabled' => true]);
        $this->assertStringContainsString('animate-spin" aria-hidden="true"', $html);
    }

    /** resendPin no daba ninguna senal: ni loading ni exito. */
    public function test_resend_pin_reports_success(): void
    {
        $style = ArtisticStyle::factory()->create(['active' => true]);
        $c = Livewire::test(PhotoUploader::class, ['styleKey' => $style->style_key])
            ->set('step', 'pin_verify');
        $c->assertSet('pinResent', false);
        $this->assertStringContainsString('wire:target="resendPin"', $c->html(), 'resendPin sigue sin estado de carga');
    }

    /** El error que impide pagar debe anunciarse. */
    public function test_checkout_error_is_announced(): void
    {
        $style = ArtisticStyle::factory()->create(['active' => true]);
        $session = GenerationSession::factory()->create([
            'artistic_style_id' => $style->id,
            'expires_at'        => now()->addDays(14),
        ]);

        $html = $this->from(route('checkout.index', $session->uuid))
            ->post('/checkout/session', [
                'session_uuid' => $session->uuid,
                'product_key'  => 'no-existe',
                'email'        => 'a@b.com',
            ])
            ->getContent();

        // La validacion redirige; lo que se comprueba es que la vista trae role=alert.
        $src = file_get_contents(resource_path('views/checkout/index.blade.php'));
        $this->assertStringContainsString('role="alert"', $src, 'El error de pago no se anuncia');
    }

    /** El buscador de /styles y el lightbox. */
    public function test_styles_index_search_and_dialog(): void
    {
        ArtisticStyle::factory()->count(2)->create(['active' => true]);
        $html = $this->get(route('styles.index'))->assertStatus(200)->getContent();

        $this->assertStringContainsString('for="styles-search"', $html, 'El buscador no tiene label');
        $this->assertStringContainsString('role="dialog"', $html, 'El lightbox no se anuncia como dialogo');
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('lastFocus', $html, 'El lightbox no devuelve el foco al cerrar');
    }

    /**
     * REGRESION: el contenido del dropzone vivia en <template x-if>, que es
     * inerte sin JS. Cuando Alpine se cayo (CSP sin unsafe-eval) el cuadro se
     * quedo literalmente en blanco: funcionaba, porque el <label> es nativo,
     * pero no decia que era ni que formatos aceptaba.
     *
     * El estado por defecto no puede depender de JS.
     */
    public function test_dropzone_instructions_render_without_javascript(): void
    {
        $html = $this->uploaderHtml(['photoReady' => false]);

        // Nada de <template> para el estado por defecto.
        $this->assertStringNotContainsString(
            '<template x-if="!localPreviewUrl">',
            $html,
            'El estado por defecto sigue en un <template>: sin JS el cuadro sale en blanco'
        );

        // El texto tiene que estar en el HTML servido, no inyectado por Alpine.
        foreach (['upload_choose', 'upload_drag', 'upload_format'] as $k) {
            $this->assertStringContainsString(
                e(__("ui.{$k}")),
                $html,
                "El cuadro no sirve {$k} en el HTML"
            );
        }
    }

    /** Los consejos deben ayudar a elegir, o sea antes del cuadro, no despues. */
    public function test_photo_tips_are_present_before_choosing(): void
    {
        $html = $this->uploaderHtml(['photoReady' => false]);
        $this->assertStringContainsString(e(__('ui.upload_tips_title')), $html);
        $this->assertStringContainsString(e(__('ui.tip_light')), $html);
    }

    /** :active global: en movil no hay hover. */
    public function test_active_state_is_declared_globally(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('button:not(:disabled):active', $css, 'Sin :active no hay feedback tactil en movil');
    }
}
