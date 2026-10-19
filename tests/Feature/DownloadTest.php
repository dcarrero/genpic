<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    // Las descargas requieren signed URL (v1.3): generar con URL::signedRoute().
    // Sin firma el middleware ValidateSignature devuelve 403 antes de llegar al
    // controlador, por lo que los tests usan signed URLs para verificar lógica real.

    private function signedDownloadUrl(Order $order, string $format): string
    {
        return URL::signedRoute('secure.export', [
            'order'  => $order->uuid,
            'format' => $format,
        ]);
    }

    public function test_download_requires_paid_order(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->get($this->signedDownloadUrl($order, 'web_basic'));
        $response->assertStatus(403);
    }

    public function test_download_rejects_if_session_purged(): void
    {
        $order = Order::factory()->paid()->create();
        $order->session->update(['purged_at' => now()]);

        $response = $this->get($this->signedDownloadUrl($order, 'web_basic'));
        $response->assertStatus(410);
    }

    public function test_download_serves_file_for_paid_order(): void
    {
        Storage::fake('local');

        $order = Order::factory()->paid()->create();
        $exportPath = 'exports/test/web_basic_abc.jpg';
        Storage::disk('local')->put($exportPath, 'fake image content');

        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'export_paths' => ['web_basic' => $exportPath],
            'exports_ready'=> true,
        ]);

        $response = $this->get($this->signedDownloadUrl($order, 'web_basic'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    public function test_download_rejects_unknown_format(): void
    {
        $order = Order::factory()->paid()->create();
        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'export_paths' => ['web_basic' => 'exports/test/file.jpg'],
            'exports_ready'=> true,
        ]);

        $response = $this->get($this->signedDownloadUrl($order, 'nonexistent_format'));
        $response->assertStatus(404);
    }
}
