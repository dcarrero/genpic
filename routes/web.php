<?php

use App\Http\Controllers\Admin\AdminMediaController;
use App\Http\Controllers\FreeDownloadController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MyDownloadsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortraitAccessController;
use App\Http\Controllers\SecureImageController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StylesController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Middleware\RequireAdminAccess;
use App\Http\Middleware\RestrictAdminByIp;
use App\Services\CachedQueries;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Localized routes — ES (default) + EN (/en/)
|--------------------------------------------------------------------------
*/

// Marketing opt-out / unsubscribe — GDPR (sin locale ni auth)
Route::get('/unsubscribe', [UnsubscribeController::class, 'show'])->name('unsubscribe')->middleware('signed');
Route::post('/unsubscribe', [UnsubscribeController::class, 'process'])->name('unsubscribe.process');

// Desuscripción pública para face.co (sin signed URL, formulario con email)
Route::get('/facecounsus', [UnsubscribeController::class, 'facecoForm'])->name('facecounsus');
Route::post('/facecounsus', [UnsubscribeController::class, 'facecoProcess'])->name('facecounsus.process')->middleware('throttle:5,1');

// Página de descarga gratuita con upsell (signed URL desde email)
Route::get('/free-download/{order:uuid}', [FreeDownloadController::class, 'show'])
    ->name('free-download.page')
    ->middleware('signed');

// Webhook Stripe — sin locale ni CSRF, rate limit alto para evitar flood
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe')
    ->middleware('throttle:500,1');

// Sitemap XML
Route::get('/sitemap.xml', function () {
    // La clave lleva el estado de cierre: al activar CLOSED_MODE el sitemap se
    // regenera solo, en vez de seguir anunciando /create (que ya devuelve 410)
    // durante los 7 días que dura la caché.
    $cacheKey = 'sitemap:xml:'.(config('genpic.closing.enabled') ? 'closed' : 'open');

    $xml = Cache::remember($cacheKey, 604800, function () {
        $styles = CachedQueries::activeStyles();
        $base = config('app.url');
        $locales = config('genpic.locales', ['es', 'en', 'fr', 'pt', 'it']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        // Helper: generar URL con hreflang alternates para todos los idiomas
        $addUrl = function (string $path, string $changefreq, string $priority) use (&$xml, $base, $locales) {
            foreach ($locales as $locale) {
                $prefix = $locale === 'es' ? '' : "/{$locale}";
                $loc = $base.$prefix.$path;
                $xml .= "<url><loc>{$loc}</loc>";
                foreach ($locales as $alt) {
                    $altPrefix = $alt === 'es' ? '' : "/{$alt}";
                    $xml .= '<xhtml:link rel="alternate" hreflang="'.$alt.'" href="'.$base.$altPrefix.$path.'"/>';
                }
                $xml .= "<changefreq>{$changefreq}</changefreq><priority>{$priority}</priority></url>\n";
            }
        };

        // Landing
        $addUrl('', 'weekly', '1.0');

        // Styles gallery
        $addUrl('/styles', 'weekly', '0.9');

        // Individual styles
        foreach ($styles as $style) {
            $addUrl("/style/{$style->style_key}", 'monthly', '0.7');
        }

        // Gallery
        $addUrl('/gallery', 'weekly', '0.8');

        // Create — fuera del sitemap si el servicio esta cerrado (devuelve 410).
        // En su lugar entra la pagina de cierre, con slug localizado como las landings.
        if (config('genpic.closing.enabled')) {
            foreach ($locales as $locale) {
                $prefix = $locale === 'es' ? '' : "/{$locale}";
                $slug = trans('ui.closing_slug', [], $locale);
                $xml .= "<url><loc>{$base}{$prefix}/{$slug}</loc>";
                foreach ($locales as $alt) {
                    $altPrefix = $alt === 'es' ? '' : "/{$alt}";
                    $altSlug = trans('ui.closing_slug', [], $alt);
                    $xml .= '<xhtml:link rel="alternate" hreflang="'.$alt.'" href="'.$base.$altPrefix.'/'.$altSlug.'"/>';
                }
                $xml .= "<changefreq>yearly</changefreq><priority>0.5</priority></url>\n";
            }
        } else {
            $addUrl('/create', 'monthly', '0.8');
        }

        // Landing pages (slugs localizados por idioma)
        $landingSlugs = [
            'lp_slug_professional', 'lp_slug_valentines', 'lp_slug_linkedin',
            'lp_slug_friends', 'lp_slug_family', 'lp_slug_tinder',
            'lp_slug_gift', 'lp_slug_painting', 'lp_slug_mothers', 'lp_slug_cv',
        ];
        foreach ($landingSlugs as $slugKey) {
            foreach ($locales as $locale) {
                $prefix = $locale === 'es' ? '' : "/{$locale}";
                $slug = trans("ui.{$slugKey}", [], $locale);
                $loc = $base.$prefix.'/'.$slug;
                $xml .= "<url><loc>{$loc}</loc>";
                foreach ($locales as $alt) {
                    $altPrefix = $alt === 'es' ? '' : "/{$alt}";
                    $altSlug = trans("ui.{$slugKey}", [], $alt);
                    $xml .= '<xhtml:link rel="alternate" hreflang="'.$alt.'" href="'.$base.$altPrefix.'/'.$altSlug.'"/>';
                }
                $xml .= "<changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
            }
        }

        // Legal (solo ES)
        foreach (['/legal' => '0.2', '/privacy' => '0.3', '/terms' => '0.3', '/cookies' => '0.2'] as $page => $priority) {
            $xml .= "<url><loc>{$base}{$page}</loc><changefreq>yearly</changefreq><priority>{$priority}</priority></url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    });

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

// robots.txt — dinámico para que la línea Sitemap salga de APP_URL. No puede
// haber un public/robots.txt: el servidor web lo serviría antes que esta ruta
// (lo mismo vale para public/sitemap.xml).
Route::get('/robots.txt', function () {
    $txt = implode("\n", [
        'User-agent: *',
        'Allow: /',
        'Disallow: /secure/',
        'Disallow: /checkout/',
        'Disallow: /my-downloads/',
        'Disallow: /portrait/',
        'Disallow: /livewire/',
        'Disallow: /unsubscribe',
        '',
        'Sitemap: '.url('/sitemap.xml'),
    ])."\n";

    return response($txt, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

// 301 redirects: slugs españoles que estaban incorrectamente en URLs de otros idiomas
// (Google ya indexó /fr/san-valentin, /en/fotos-profesionales, etc.)
$esLandingSlugs = [
    'lp_slug_professional' => 'fotos-profesionales',
    'lp_slug_valentines' => 'san-valentin',
    'lp_slug_linkedin' => 'foto-linkedin',
    'lp_slug_friends' => 'fotos-con-amigos',
    'lp_slug_family' => 'fotos-de-familia',
    'lp_slug_tinder' => 'fotos-para-tinder',
    'lp_slug_gift' => 'regalo-personalizado-con-foto',
    'lp_slug_painting' => 'convertir-foto-en-cuadro',
    'lp_slug_mothers' => 'dia-de-la-madre',
    'lp_slug_cv' => 'foto-para-curriculum',
];

foreach (array_diff(config('genpic.locales', ['es', 'en']), ['es']) as $loc) {
    foreach ($esLandingSlugs as $slugKey => $esSlug) {
        $localizedSlug = trans("ui.{$slugKey}", [], $loc);
        if ($localizedSlug !== $esSlug) {
            Route::redirect("/{$loc}/{$esSlug}", "/{$loc}/{$localizedSlug}", 301);
        }
    }
}

// Visor de imágenes del admin — streaming privado, misma protección que Filament:
// auth 'web' + canAccessPanel (admin_emails) + allowlist de IP.
// Nota: no usamos Filament\Http\Middleware\Authenticate fuera del panel (necesita panel context);
// usamos 'auth' estándar + RequireAdminAccess que verifica canAccessPanel + RestrictAdminByIp.
Route::middleware([
    'web',
    RequireAdminAccess::class,
    RestrictAdminByIp::class,
])->prefix(config('genpic.admin_path', 'admin').'/media')->name('admin.media.')->group(function () {
    Route::get('/session/{session:uuid}/original', [AdminMediaController::class, 'original'])
        ->name('original');
    Route::get('/generation/{generation}/{kind}', [AdminMediaController::class, 'generation'])
        ->name('generation')
        ->whereIn('kind', ['raw', 'watermarked']);
});

// Rutas localizadas
$localizedRoutes = function () {
    // Páginas públicas cacheables (testimonios, galería featured se rotan al expirar cache)
    Route::middleware('cache.page')->group(function () {
        Route::get('/', [LandingController::class, 'index'])->name('landing');
        Route::get('/styles', [StylesController::class, 'index'])->name('styles.index');
        Route::get('/style/{styleKey}', [StylesController::class, 'show'])->name('styles.show');
        Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
        Route::get('/gallery/{id}', [GalleryController::class, 'show'])->name('gallery.show')->where('id', '[0-9]+');

        // Landings específicas (slug localizado por idioma)
        Route::get('/'.__('ui.lp_slug_professional'), [LandingController::class, 'professional'])->name('landing.professional');
        Route::get('/'.__('ui.lp_slug_valentines'), [LandingController::class, 'valentines'])->name('landing.valentines');
        Route::get('/'.__('ui.lp_slug_linkedin'), [LandingController::class, 'linkedin'])->name('landing.linkedin');
        Route::get('/'.__('ui.lp_slug_friends'), [LandingController::class, 'friends'])->name('landing.friends');
        Route::get('/'.__('ui.lp_slug_family'), [LandingController::class, 'family'])->name('landing.family');
        Route::get('/'.__('ui.lp_slug_tinder'), [LandingController::class, 'tinder'])->name('landing.tinder');
        Route::get('/'.__('ui.lp_slug_gift'), [LandingController::class, 'gift'])->name('landing.gift');
        Route::get('/'.__('ui.lp_slug_painting'), [LandingController::class, 'photoToPainting'])->name('landing.painting');
        Route::get('/'.__('ui.lp_slug_mothers'), [LandingController::class, 'mothersDay'])->name('landing.mothers');
        Route::get('/'.__('ui.lp_slug_cv'), [LandingController::class, 'cvPhoto'])->name('landing.cv');
    });

    // Imágenes galería (ya tienen cache HTTP 7d en headers)
    Route::get('/gallery/image/{id}/{type}', [GalleryController::class, 'image'])->name('gallery.image');

    // Wizard de generación
    Route::prefix('create')->name('create.')->group(function () {
        Route::get('/', [GenerationController::class, 'index'])->name('index');
        Route::post('/upload', [GenerationController::class, 'upload'])->name('upload')->middleware('generation.limit');
        Route::post('/email', [GenerationController::class, 'captureEmail'])->name('email')->middleware('throttle:10,1');
        Route::get('/preview/{session:uuid}', [GenerationController::class, 'preview'])->name('preview');
        Route::get('/status/{session:uuid}', [GenerationController::class, 'status'])->name('status')->middleware('throttle:30,1');
    });

    // Acceso seguro al retrato (email + PIN)
    Route::prefix('portrait')->name('portrait.')->group(function () {
        Route::get('/{session:uuid}', [PortraitAccessController::class, 'showEmailForm'])->name('access');
        Route::post('/{session:uuid}/send-pin', [PortraitAccessController::class, 'sendPin'])->name('send-pin')->middleware('throttle:3,5');
        Route::get('/{session:uuid}/verify', [PortraitAccessController::class, 'showPinForm'])->name('verify');
        Route::post('/{session:uuid}/verify', [PortraitAccessController::class, 'verifyPin'])->name('check-pin')->middleware('throttle:5,1');
    });

    // Checkout
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/success', [PaymentController::class, 'success'])->name('success');
        Route::get('/cancel', [PaymentController::class, 'cancel'])->name('cancel');
        Route::post('/session', [PaymentController::class, 'createSession'])->name('session')->middleware('throttle:10,1');
        Route::post('/validate-coupon', [PaymentController::class, 'validateCoupon'])->name('validate-coupon')->middleware('throttle:20,1');
        Route::get('/{session:uuid}', [PaymentController::class, 'index'])->name('index');
    });

    // Mis descargas (recuperación por email + PIN)
    Route::get('/my-downloads', [MyDownloadsController::class, 'showForm'])->name('downloads.form');
    Route::post('/my-downloads/send-pin', [MyDownloadsController::class, 'sendPin'])->name('downloads.send-pin')->middleware('throttle:3,5');
    Route::get('/my-downloads/pin', [MyDownloadsController::class, 'showPinForm'])->name('downloads.pin');
    Route::post('/my-downloads/verify', [MyDownloadsController::class, 'verifyPin'])->name('downloads.verify-pin')->middleware('throttle:5,1');
    Route::get('/my-downloads/list', [MyDownloadsController::class, 'showList'])->name('downloads.list');

    // Cierre del servicio — se registra siempre (el layout enlaza aqui cuando
    // CLOSED_MODE esta activo) y explica por que se apago GenPic.
    // Closure y no Route::view(): esta ultima registra 'view' y 'status' como
    // parametros de la ruta, y route() los serializa como query string — el
    // hreflang salia como /fr/fermeture?view=closed&status=200, sin coincidir
    // con el canonical.
    Route::get('/'.__('ui.closing_slug'), fn () => view('closed'))->name('closing');

    // Legal
    Route::get('/legal', [LegalController::class, 'legal'])->name('legal.notice');
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
    Route::get('/cookies', [LegalController::class, 'cookies'])->name('legal.cookies');

    // Imágenes seguras — NUNCA acceso directo a archivos
    Route::prefix('secure')->name('secure.')->group(function () {
        Route::get('/original/{session:uuid}', [SecureImageController::class, 'original'])->name('original');
        Route::get('/preview/{session:uuid}', [SecureImageController::class, 'watermarked'])->name('watermarked');
        // Descargas con signed URLs (72h) — requiere firma válida
        Route::get('/download/{order}/{format}', [SecureImageController::class, 'export'])->name('export')->middleware('signed');
        Route::get('/download-zip/{order}', [SecureImageController::class, 'downloadZip'])->name('zip')->middleware('signed');
    });
};

// ES — rutas por defecto (sin prefijo)
Route::middleware('set-locale')->group($localizedRoutes);

// Otros idiomas — rutas con prefijo (/en/, /fr/, /pt/, /it/)
// Setear locale temporalmente para que __() resuelva los slugs correctos de cada idioma
foreach (array_diff(config('genpic.locales', ['es', 'en']), ['es']) as $loc) {
    app()->setLocale($loc);
    Route::prefix($loc)->name("{$loc}.")->middleware('set-locale')->group($localizedRoutes);
}
app()->setLocale('es');
