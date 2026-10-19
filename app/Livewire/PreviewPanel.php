<?php

namespace App\Livewire;

use App\Mail\FreeDownloadMail;
use App\Models\ArtisticStyle;
use App\Models\GenerationSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CachedQueries;
use App\Services\StorageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PreviewPanel extends Component
{
    public GenerationSession $session;
    public bool $isReady = false;
    public bool $hasFailed = false;
    public ?string $watermarkedUrl = null;
    public ?string $originalUrl = null;

    // Upsell state
    public bool $showUpsell = false;
    public array $selectedExtraStyles = [];

    // Free download state
    public bool $freePromoEnabled = false;
    public bool $freeDownloadClaimed = false;
    public string $freeEmail = '';

    public function mount(GenerationSession $session)
    {
        $this->session = $session;
        $this->freePromoEnabled = (bool) config('genpic.free_web_basic_enabled', false);
        $this->freeDownloadClaimed = $this->hasFreeOrder();
        $this->freeEmail = $session->user?->email ?? '';
        $this->checkReady();
    }

    public function checkReady()
    {
        $this->session->refresh();
        $generation = $this->session->latestGeneration;

        // Detectar fallo en la sesión o en la generación
        if ($this->session->status === 'failed' || ($generation && $generation->status === 'failed')) {
            $this->hasFailed = true;
            return;
        }

        if ($generation && $generation->watermarked_image_path) {
            $this->isReady = true;
            $this->watermarkedUrl = route('secure.watermarked', $this->session) . '?v=' . $generation->updated_at->timestamp;
            $this->originalUrl = route('secure.original', $this->session);
        }
    }

    public function resetOrientation()
    {
        $this->session->update(['ab_variant' => 'auto']);

        // Regenerar watermark sin crop
        $generation = $this->session->latestGeneration;
        if ($generation) {
            \App\Jobs\ApplyWatermarkJob::dispatch($this->session, $generation);
            $this->isReady = false;
        }
    }

    public function checkout(string $productKey)
    {
        // Si es pack_completo, mostrar selector de estilos extra
        if ($productKey === 'pack_completo') {
            $this->showUpsell = true;
            return;
        }

        $this->redirect(locale_route('checkout.index', $this->session) . '?product=' . $productKey);
    }

    public function cancelUpsell()
    {
        $this->showUpsell = false;
        $this->selectedExtraStyles = [];
    }

    public function toggleExtraStyle(string $styleKey)
    {
        if (in_array($styleKey, $this->selectedExtraStyles)) {
            $this->selectedExtraStyles = array_values(
                array_diff($this->selectedExtraStyles, [$styleKey])
            );
        } elseif (count($this->selectedExtraStyles) < 5) {
            $this->selectedExtraStyles[] = $styleKey;
        }
    }

    public function proceedToCheckout()
    {
        $url = locale_route('checkout.index', $this->session) . '?product=pack_completo';

        if (!empty($this->selectedExtraStyles)) {
            $url .= '&extras=' . implode(',', $this->selectedExtraStyles);
        }

        $this->redirect($url);
    }

    #[Computed]
    public function products()
    {
        return CachedQueries::mainProducts();
    }

    #[Computed]
    public function availableStyles()
    {
        return CachedQueries::activeStyles()
            ->where('id', '!=', $this->session->artistic_style_id);
    }

    #[Computed]
    public function extraProduct()
    {
        return CachedQueries::productByKey('extra_style');
    }

    #[Computed]
    public function upsellTotal()
    {
        $base = CachedQueries::productByKey('pack_completo')?->priceCents() ?? 999;
        $extraPrice = $this->extraProduct?->priceCents() ?? 299;
        return $base + (count($this->selectedExtraStyles) * $extraPrice);
    }

    /**
     * Reclamar descarga gratuita de web_basic.
     */
    public function claimFreeDownload()
    {
        if (!$this->freePromoEnabled || $this->freeDownloadClaimed) return;

        $this->validate(['freeEmail' => ['required', 'email:rfc', 'max:255']]);

        $locale = $this->session->locale ?? app()->getLocale();

        // 1. Crear/obtener usuario
        $user = User::firstOrCreate(
            ['email' => strtolower($this->freeEmail)],
            ['locale' => $locale, 'ip_address' => request()->ip()]
        );
        $user->update(['last_seen_at' => now()]);

        // Asociar a la sesión si no tenía usuario
        if (!$this->session->user_id) {
            $this->session->update([
                'user_id' => $user->id,
                'email_captured_at' => now(),
            ]);
        }

        // 2. Obtener producto web_basic
        $product = CachedQueries::productByKey('web_basic');
        if (!$product) {
            Log::error('Product web_basic not found for free promo');
            return;
        }

        // 3. Generar imagen web_basic con marca de cortesía
        $generation = $this->session->latestGeneration;
        if (!$generation || !$generation->raw_image_path) return;

        $storage = app(StorageService::class);
        $disk = $storage->disk();
        $rawImage = Storage::disk($disk)->get($generation->raw_image_path);

        $manager = new ImageManager(\Intervention\Image\Drivers\Imagick\Driver::class);
        $image = $manager->read($rawImage);
        $image->scaleDown(1024);

        // Marca de cortesía superpuesta
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $courtesyText = match ($locale) {
            'en' => "Image created with {$host}",
            'fr' => "Image créée avec {$host}",
            'pt' => "Imagem criada com {$host}",
            'it' => "Immagine creata con {$host}",
            default => "Imagen creada con {$host}",
        };

        $imgWidth = $image->width();
        $imgHeight = $image->height();
        $fontSize = 18;
        $yPos = $imgHeight - 14;

        // Sombra (debajo, más oscura)
        $image->text($courtesyText, (int)($imgWidth / 2) + 1, $yPos + 1, function ($font) use ($fontSize) {
            $font->filename(storage_path('fonts/DejaVuSans.ttf'));
            $font->size($fontSize);
            $font->color('rgba(0, 0, 0, 0.60)');
            $font->align('center');
            $font->valign('bottom');
        });

        // Texto principal (encima, más visible)
        $image->text($courtesyText, (int)($imgWidth / 2), $yPos, function ($font) use ($fontSize) {
            $font->filename(storage_path('fonts/DejaVuSans.ttf'));
            $font->size($fontSize);
            $font->color('rgba(255, 255, 255, 0.90)');
            $font->align('center');
            $font->valign('bottom');
        });

        $encoded = $image->toJpeg(85)->toString();
        $exportPath = sprintf('exports/%s/web_basic_free_genpic_com.jpg', $this->session->uuid);
        Storage::disk($disk)->put($exportPath, $encoded);

        // 4. Crear order a 0€ con protección contra duplicados
        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $product, $generation, $exportPath, $encoded) {
            // Re-check bajo transacción para evitar duplicados por clicks rápidos
            if ($this->hasFreeOrder()) {
                return null;
            }

            $order = Order::create([
                'generation_session_id' => $this->session->id,
                'user_id'        => $user->id,
                'uuid'           => \Illuminate\Support\Str::uuid(),
                'total_cents'    => 0,
                'currency'       => 'eur',
                'status'         => 'paid',
                'paid_at'        => now(),
                'download_ready' => true,
            ]);

            OrderItem::create([
                'order_id'           => $order->id,
                'product_id'         => $product->id,
                'generation_id'      => $generation->id,
                'unit_price_cents'   => 0,
                'total_cents'        => 0,
                'quantity'           => 1,
                'export_paths'       => ['web_basic' => $exportPath],
                'exports_ready'      => true,
                'exports_size_bytes' => strlen($encoded),
            ]);

            return $order;
        });

        if (!$order) {
            $this->freeDownloadClaimed = true;
            return;
        }

        // 5. Enviar email con descarga + upsell
        try {
            Mail::to($user)->send(new FreeDownloadMail($order, $this->session));
        } catch (\Exception $e) {
            Log::warning('FreeDownloadMail failed', ['error' => $e->getMessage()]);
        }

        $this->freeDownloadClaimed = true;

        Log::info('Free web_basic claimed', [
            'session' => $this->session->uuid,
            'email'   => $user->email,
            'order'   => $order->uuid,
        ]);
    }

    private function hasFreeOrder(): bool
    {
        return Order::where('generation_session_id', $this->session->id)
            ->where('total_cents', 0)
            ->where('status', 'paid')
            ->exists();
    }

    public function render()
    {
        return view('livewire.preview-panel');
    }
}
