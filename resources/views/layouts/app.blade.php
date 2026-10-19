{{-- Hallmark · chrome: layout · genre: editorial · design-system: design.md
     Nav y footer compartidos por todas las paginas. No es una pagina: no lleva
     macroestructura. Lo que fija: fuentes autoalojadas (preload), paleta, y el
     banner de cookies. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ui.brand') . ' — ' . __('ui.tagline') }}</title>
    <meta name="description" content="{{ $description ?? __('ui.footer_tagline') }}">

    {{-- Favicons --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="msapplication-TileImage" content="{{ asset('mstile-150x150.png') }}">
    <meta name="msapplication-TileColor" content="#7A2D3B">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#7A2D3B">

    {{-- SEO: hreflang basado en route names para soportar slugs localizados --}}
    @php
        $currentLocale = app()->getLocale();
        $allLocales = config('genpic.locales', ['es', 'en']);
        $localeMapSeo = config('genpic.locale_map', []);
        $currentRoute = \Illuminate\Support\Facades\Route::current();
        $currentRouteName = $currentRoute?->getName();
        $nonDefaultLocales = array_diff($allLocales, ['es']);
        $baseRouteName = $currentRouteName
            ? preg_replace('#^(' . implode('|', $nonDefaultLocales) . ')\.#', '', $currentRouteName)
            : null;
        $routeParams = $currentRoute?->parameters() ?? [];

        // URL equivalente en cada idioma. Se resuelve por NOMBRE DE RUTA, no
        // pegando el prefijo al path: con slugs localizados (/cierre vs
        // /fr/fermeture, /fotos-profesionales vs /fr/photos-professionnelles)
        // el path del idioma actual no existe en los demas.
        // Este mismo array alimenta el hreflang y el desplegable de idioma del
        // header, para que no puedan divergir.
        $localeUrls = [];
        foreach ($allLocales as $loc) {
            $locRouteName = ($loc === 'es') ? $baseRouteName : "{$loc}.{$baseRouteName}";
            if ($baseRouteName && $locRouteName && \Illuminate\Support\Facades\Route::has($locRouteName)) {
                $locHref = route($locRouteName, $routeParams);
            } else {
                // Sin nombre de ruta (404, rutas sueltas): el path es lo unico que hay.
                $prefixPattern = '#^(' . implode('|', $nonDefaultLocales) . ')(/|$)#';
                $basePath = preg_replace($prefixPattern, '', request()->path()) ?: '/';
                $locHref = $loc === 'es' ? url('/' . ltrim($basePath, '/')) : url('/' . $loc . '/' . ltrim($basePath, '/'));
            }
            $localeUrls[$loc] = rtrim($locHref, '/') ?: url('/');
        }
    @endphp
    <link rel="canonical" href="{{ url()->current() }}">
    @foreach($allLocales as $loc)
        <link rel="alternate" hreflang="{{ $loc }}" href="{{ $localeUrls[$loc] }}">
    @endforeach
    @php
        $defaultRouteName = $baseRouteName && \Illuminate\Support\Facades\Route::has($baseRouteName) ? $baseRouteName : null;
    @endphp
    <link rel="alternate" hreflang="x-default" href="{{ $defaultRouteName ? route($defaultRouteName, $routeParams) : url('/') }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $title ?? __('ui.brand') . ' — ' . __('ui.tagline') }}">
    <meta property="og:description" content="{{ $description ?? __('ui.footer_tagline') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/og-preview.jpg') }}">
    <meta property="og:site_name" content="GenPic">
    <meta property="og:locale" content="{{ $localeMapSeo[$currentLocale]['og'] ?? 'es_ES' }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? __('ui.brand') . ' — ' . __('ui.tagline') }}">
    <meta name="twitter:description" content="{{ $description ?? __('ui.footer_tagline') }}">
    <meta name="twitter:image" content="{{ asset('img/og-preview.jpg') }}">

    {{-- SEO: theme-color, preconnect, robots --}}
    <meta name="theme-color" content="#7A2D3B">
    {{-- Fuentes autoalojadas: sin preconnect a Google (RGPD — la IP del visitante
         ya no sale del servidor). En su lugar, preload de los dos woff2 que usa
         el pliegue; el resto los pide el CSS cuando hacen falta. --}}
    <link rel="preload" href="{{ asset('fonts/source-serif-4-var-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/barlow-semi-condensed-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    @if(isset($robots))
        <meta name="robots" content="{{ $robots }}">
    @elseif(request()->is('*/checkout*', '*/my-downloads*', '*/portrait/*', '*/free-download/*', 'checkout*', 'my-downloads*', 'portrait/*', 'free-download/*'))
        <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- JSON-LD: Organization (global) --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "GenPic",
        "url": "{{ config('app.url') }}",
        "logo": "{{ asset('img/og-preview.jpg') }}",
        "description": "{{ __('ui.footer_tagline') }}",
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Service",
            "email": "{{ config('genpic.contact_email') }}"
        }
    }
    </script>
    @stack('jsonld')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- GA4 híbrido: cookieless por defecto (sin consentimiento), cookies completas si acepta --}}
    @php
        $gaId = \App\Models\SiteSetting::get('google_analytics_id');
        $headScripts = \App\Models\SiteSetting::get('head_scripts');
        $bodyScripts = \App\Models\SiteSetting::get('body_scripts');
    @endphp
    @if($gaId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    // Modo cookieless por defecto — no necesita consentimiento RGPD
    gtag('consent', 'default', { analytics_storage: 'denied', ad_storage: 'denied' });
    gtag('config', '{{ $gaId }}', { anonymize_ip: true, cookie_flags: 'SameSite=None;Secure' });
    </script>
    @endif
</head>
<body class="bg-champagne text-pmf-text font-sans antialiased">
    {{-- Cookie Consent Banner — primer hijo de body para evitar problemas de z-index/stacking --}}
    <div id="cc-banner" style="position:fixed !important; bottom:0 !important; left:0 !important; right:0 !important; z-index:99999 !important; padding:1rem;">
        <div style="max-width:56rem; margin:0 auto;" class="bg-white rounded-xl shadow-2xl overflow-hidden border border-pmf-border">
            <div id="cc-main" class="p-5 sm:p-6">
                <div class="sm:flex sm:items-start sm:gap-4">
                    <div class="flex-1">
                        <h3 class="font-serif text-base font-bold text-pmf-text mb-1">{{ __('ui.cookie_banner_title') }}</h3>
                        <p class="text-sm text-pmf-secondary leading-relaxed">
                            {{ __('ui.cookie_banner_text') }}
                            <a href="{{ locale_route('legal.cookies') }}" class="text-burgundy hover:underline font-medium">{{ __('ui.footer_cookies') }}</a>
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 mt-4 sm:mt-0 sm:flex-shrink-0">
                        <button onclick="ccShowSettings()" class="px-4 py-2 text-sm font-medium text-pmf-secondary border border-pmf-border rounded-lg hover:bg-champagne transition">{{ __('ui.cookie_settings') }}</button>
                        <button onclick="ccSave('essential')" class="px-4 py-2 text-sm font-medium text-pmf-secondary border border-pmf-border rounded-lg hover:bg-champagne transition">{{ __('ui.cookie_reject') }}</button>
                        <button onclick="ccSave('all')" class="px-5 py-2 text-sm font-bold text-white bg-burgundy rounded-lg hover:bg-burgundy-dark transition">{{ __('ui.cookie_accept') }}</button>
                    </div>
                </div>
            </div>
            <div id="cc-settings" class="p-5 sm:p-6" style="display:none;">
                <h3 class="font-serif text-base font-bold text-pmf-text mb-4">{{ __('ui.cookie_settings') }}</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-champagne/50 rounded-lg">
                        <div>
                            <p class="text-sm font-semibold text-pmf-text">{{ __('ui.cookie_essential_title') }}</p>
                            <p class="text-xs text-pmf-muted mt-0.5">{{ __('ui.cookie_essential_desc') }}</p>
                        </div>
                        <span class="text-xs font-medium text-pmf-muted bg-pmf-border/30 px-2 py-1 rounded">{{ __('ui.cookie_always_active') }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-champagne/50 rounded-lg">
                        <div>
                            <p class="text-sm font-semibold text-pmf-text">{{ __('ui.cookie_analytics_title') }}</p>
                            <p class="text-xs text-pmf-muted mt-0.5">{{ __('ui.cookie_analytics_desc') }}</p>
                        </div>
                        <button id="cc-toggle" onclick="ccToggleAnalytics()" style="position:relative; display:inline-flex; height:1.5rem; width:2.75rem; flex-shrink:0; cursor:pointer; border-radius:9999px; background-color:#D1C8C0; border:none; padding:0; transition:background-color 0.2s;">
                            <span id="cc-toggle-dot" style="display:inline-block; height:1.25rem; width:1.25rem; border-radius:9999px; background-color:white; box-shadow:0 1px 3px rgba(0,0,0,0.2); margin-top:0.125rem; margin-left:0.125rem; transition:margin-left 0.2s;"></span>
                        </button>
                    </div>
                </div>
                <div class="flex gap-2 mt-5 justify-end">
                    <button onclick="ccHideSettings()" class="px-4 py-2 text-sm text-pmf-secondary border border-pmf-border rounded-lg hover:bg-champagne transition">{{ __('ui.cookie_back') }}</button>
                    <button onclick="ccSaveSettings()" class="px-5 py-2 text-sm font-bold text-white bg-burgundy rounded-lg hover:bg-burgundy-dark transition">{{ __('ui.cookie_save') }}</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    var ccAnalytics = false;
    (function(){
        var b = document.getElementById('cc-banner');
        var consent = localStorage.getItem('cookieConsent');
        if (consent) {
            b.style.display = 'none';
            try {
                var d = JSON.parse(consent);
                ccAnalytics = !!d.analytics;
                if (d.analytics) ccLoadAnalytics();
            } catch(e) {}
        }
    })();
    function ccShowSettings() {
        document.getElementById('cc-main').style.display = 'none';
        document.getElementById('cc-settings').style.display = 'block';
        // Sincronizar toggle con el estado actual
        var t = document.getElementById('cc-toggle');
        var dot = document.getElementById('cc-toggle-dot');
        t.style.backgroundColor = ccAnalytics ? '#7A2D3B' : '#D1C8C0';
        dot.style.marginLeft = ccAnalytics ? '1.375rem' : '0.125rem';
    }
    function ccHideSettings() {
        document.getElementById('cc-settings').style.display = 'none';
        document.getElementById('cc-main').style.display = 'block';
    }
    function ccToggleAnalytics() {
        ccAnalytics = !ccAnalytics;
        var t = document.getElementById('cc-toggle');
        var d = document.getElementById('cc-toggle-dot');
        t.style.backgroundColor = ccAnalytics ? '#7A2D3B' : '#D1C8C0';
        d.style.marginLeft = ccAnalytics ? '1.375rem' : '0.125rem';
    }
    function ccSaveSettings() { ccSave(ccAnalytics ? 'all' : 'essential'); }
    function ccSave(level) {
        var analytics = (level === 'all');
        ccAnalytics = analytics;
        localStorage.setItem('cookieConsent', JSON.stringify({ level: level, analytics: analytics, date: new Date().toISOString() }));
        document.cookie = 'cookieConsent=' + level + ';path=/;max-age=' + (365*24*60*60) + ';SameSite=Lax';
        document.getElementById('cc-banner').style.display = 'none';
        if (analytics) ccLoadAnalytics();
    }
    function ccReopenBanner() {
        var b = document.getElementById('cc-banner');
        b.style.display = '';
        ccShowSettings();
    }
    function ccLoadAnalytics() {
        @if($gaId ?? false)
        // GA4 ya está cargado en <head> en modo cookieless.
        // Aquí solo upgradeamos a cookies completas tras consentimiento.
        if (typeof gtag === 'function') {
            gtag('consent', 'update', { analytics_storage: 'granted' });
        }
        @endif
        @if($headScripts ?? false)
        if (!document.getElementById('cc-head-scripts')) {
            var h = document.createElement('div'); h.id = 'cc-head-scripts';
            h.innerHTML = {!! json_encode($headScripts) !!};
            var scripts = h.querySelectorAll('script');
            scripts.forEach(function(os) {
                var ns = document.createElement('script');
                if (os.src) { ns.src = os.src; ns.async = true; } else { ns.textContent = os.textContent; }
                document.head.appendChild(ns);
            });
        }
        @endif
        @if($bodyScripts ?? false)
        if (!document.getElementById('cc-body-scripts')) {
            var w = document.createElement('div'); w.id = 'cc-body-scripts';
            w.innerHTML = {!! json_encode($bodyScripts) !!};
            var scripts = w.querySelectorAll('script');
            scripts.forEach(function(os) {
                var ns = document.createElement('script');
                if (os.src) { ns.src = os.src; ns.async = true; } else { ns.textContent = os.textContent; }
                document.body.appendChild(ns);
            });
        }
        @endif
    }
    </script>

    {{-- Aviso de cierre del servicio (CLOSED_MODE) --}}
    @include('components.closing-banner')

    {{-- Header --}}
    <header class="bg-burgundy" x-data="{ mobileMenu: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
            <div class="flex items-center gap-3 sm:gap-4">
                @php $currentLocale = app()->getLocale(); @endphp
                <a href="{{ $currentLocale === 'es' ? '/' : '/' . $currentLocale }}" class="font-serif text-lg sm:text-xl font-bold text-white">{{ __('ui.brand') }}</a>
                {{-- Language switcher dropdown --}}
                @php
                    // Las URLs por idioma vienen del <head>: mismo calculo por
                    // nombre de ruta que el hreflang, para no volver a pegar el
                    // prefijo a un slug que en otro idioma no existe.
                    $locales = $allLocales;
                    $localeMap = $localeMapSeo;
                @endphp
                <div class="relative" x-data="{ langOpen: false }" @click.away="langOpen = false">
                    <button @click="langOpen = !langOpen" class="flex items-center gap-1 px-2 py-1 rounded text-xs text-white bg-white/10 hover:bg-white/20 transition">
                        <span>{{ $localeMap[$currentLocale]['flag'] ?? '' }}</span>
                        <span class="font-semibold uppercase">{{ $currentLocale }}</span>
                        <svg class="w-3 h-3 transition" :class="langOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="langOpen" x-cloak x-transition class="absolute top-full left-0 mt-1 bg-white rounded-lg shadow-lg border border-pmf-border py-1 min-w-[140px] z-50">
                        @foreach($locales as $loc)
                            <a href="{{ $localeUrls[$loc] ?? '/' }}"
                               class="flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-champagne transition {{ $loc === $currentLocale ? 'font-bold text-burgundy bg-burgundy-soft/30' : 'text-pmf-text' }}">
                                <span>{{ $localeMap[$loc]['flag'] ?? '' }}</span>
                                <span>{{ $localeMap[$loc]['name'] ?? strtoupper($loc) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @hasSection('stepper')
                @yield('stepper')
            @else
                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ locale_route('styles.index') }}" class="text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_styles') }}</a>
                    <a href="{{ locale_route('gallery.index') }}" class="text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_gallery') }}</a>
                    <a href="#how-it-works" class="text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_how') }}</a>
                    <a href="#pricing" class="text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_pricing') }}</a>
                    <a href="#faq" class="text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_faq') }}</a>
                    @if(config('genpic.closing.enabled'))
                        <a href="{{ locale_route('downloads.form') }}" class="bg-white text-burgundy text-sm font-semibold px-5 py-2.5 rounded-lg hover:bg-champagne transition">{{ __('ui.footer_downloads') }}</a>
                    @else
                        <a href="{{ locale_route('create.index') }}" class="bg-white text-burgundy text-sm font-semibold px-5 py-2.5 rounded-lg hover:bg-champagne transition">{{ __('ui.nav_cta') }}</a>
                    @endif
                </nav>
                {{-- Hamburger button (mobile) --}}
                <button x-on:click="mobileMenu = !mobileMenu" class="md:hidden text-white p-1" aria-label="Menú">
                    <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileMenu" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            @endif
        </div>
        {{-- Mobile menu dropdown --}}
        @unless(View::hasSection('stepper'))
        <div x-show="mobileMenu" x-cloak x-transition class="md:hidden bg-burgundy-dark border-t border-white/10 px-4 py-4 space-y-3">
            <a href="{{ locale_route('styles.index') }}" class="block text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_styles') }}</a>
            <a href="{{ locale_route('gallery.index') }}" class="block text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_gallery') }}</a>
            <a href="#how-it-works" x-on:click="mobileMenu = false" class="block text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_how') }}</a>
            <a href="#pricing" x-on:click="mobileMenu = false" class="block text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_pricing') }}</a>
            <a href="#faq" x-on:click="mobileMenu = false" class="block text-white/80 text-sm font-medium hover:text-white transition">{{ __('ui.nav_faq') }}</a>
            @if(config('genpic.closing.enabled'))
                <a href="{{ locale_route('downloads.form') }}" class="block bg-white text-burgundy text-sm font-semibold px-5 py-2.5 rounded-lg text-center hover:bg-champagne transition mt-2">{{ __('ui.footer_downloads') }}</a>
            @else
                <a href="{{ locale_route('create.index') }}" class="block bg-white text-burgundy text-sm font-semibold px-5 py-2.5 rounded-lg text-center hover:bg-champagne transition mt-2">{{ __('ui.nav_cta') }}</a>
            @endif
        </div>
        @endunless
    </header>

    {{-- Content --}}
    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    {{-- Footer --}}
    @hasSection('hide-footer')
    @else
        <footer class="bg-pmf-dark text-champagne">
            <div class="max-w-7xl mx-auto px-6 py-12">
                {{-- Brand + tagline arriba --}}
                <div class="mb-8">
                    <h3 class="font-serif text-lg font-bold mb-1">{{ __('ui.brand') }}</h3>
                    <p class="text-sm text-pmf-muted-dark leading-relaxed">{{ __('ui.footer_tagline') }}</p>
                </div>
                {{-- 4 columnas --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
                    <div>
                        <h4 class="text-xs font-bold text-pmf-muted-dark tracking-widest uppercase mb-3">{{ __('ui.footer_product') }}</h4>
                        <ul class="space-y-2 text-sm">
                            @if(config('genpic.closing.enabled'))
                                <li><a href="{{ locale_route('closing') }}" class="hover:text-white transition">{{ __('ui.closing_banner_link') }}</a></li>
                            @else
                                <li><a href="{{ locale_route('create.index') }}" class="hover:text-white transition">{{ __('ui.footer_create') }}</a></li>
                            @endif
                            <li><a href="{{ locale_route('styles.index') }}" class="hover:text-white transition">{{ __('ui.footer_styles') }}</a></li>
                            <li><a href="{{ locale_route('gallery.index') }}" class="hover:text-white transition">{{ __('ui.nav_gallery') }}</a></li>
                            <li><a href="#pricing" class="hover:text-white transition">{{ __('ui.footer_pricing') }}</a></li>
                            <li><a href="#faq" class="hover:text-white transition">{{ __('ui.footer_faq') }}</a></li>
                            <li><a href="{{ locale_route('downloads.form') }}" class="hover:text-white transition">{{ __('ui.footer_downloads') }}</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-pmf-muted-dark tracking-widest uppercase mb-3">{{ __('ui.footer_landings') }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ locale_route('landing.professional') }}" class="hover:text-white transition">{{ __('ui.footer_lp_professional') }}</a></li>
                            <li><a href="{{ locale_route('landing.linkedin') }}" class="hover:text-white transition">{{ __('ui.footer_lp_linkedin') }}</a></li>
                            <li><a href="{{ locale_route('landing.cv') }}" class="hover:text-white transition">{{ __('ui.footer_lp_cv') }}</a></li>
                            <li><a href="{{ locale_route('landing.painting') }}" class="hover:text-white transition">{{ __('ui.footer_lp_painting') }}</a></li>
                            <li><a href="{{ locale_route('landing.family') }}" class="hover:text-white transition">{{ __('ui.footer_lp_family') }}</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-pmf-muted-dark tracking-widest uppercase mb-3">{{ __('ui.footer_landings_2') }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ locale_route('landing.friends') }}" class="hover:text-white transition">{{ __('ui.footer_lp_friends') }}</a></li>
                            <li><a href="{{ locale_route('landing.valentines') }}" class="hover:text-white transition">{{ __('ui.footer_lp_valentines') }}</a></li>
                            <li><a href="{{ locale_route('landing.tinder') }}" class="hover:text-white transition">{{ __('ui.footer_lp_tinder') }}</a></li>
                            <li><a href="{{ locale_route('landing.gift') }}" class="hover:text-white transition">{{ __('ui.footer_lp_gift') }}</a></li>
                            <li><a href="{{ locale_route('landing.mothers') }}" class="hover:text-white transition">{{ __('ui.footer_lp_mothers') }}</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-pmf-muted-dark tracking-widest uppercase mb-3">{{ __('ui.footer_legal') }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ locale_route('legal.privacy') }}" class="hover:text-white transition">{{ __('ui.footer_privacy') }}</a></li>
                            <li><a href="{{ locale_route('legal.terms') }}" class="hover:text-white transition">{{ __('ui.footer_terms') }}</a></li>
                            <li><a href="{{ locale_route('legal.notice') }}" class="hover:text-white transition">{{ __('ui.footer_notice') }}</a></li>
                            <li><a href="{{ locale_route('legal.cookies') }}" class="hover:text-white transition">{{ __('ui.footer_cookies') }}</a></li>
                            <li><button type="button" onclick="ccReopenBanner()" class="hover:text-white transition">{{ __('ui.footer_manage_cookies') }}</button></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-white/10 pt-6 flex flex-col md:flex-row justify-between items-center text-xs text-white/30">
                    <div>
                        <span>&copy; {{ date('Y') }} {{ parse_url(config('app.url'), PHP_URL_HOST) }} v{{ config('app.version', '1.6.3') }} - {{ config('genpic.company.name') }} — {{ __('ui.footer_rights') }}</span>
                        <br>
                        <span>{{ __('ui.footer_made_with_love') }}</span>
                    </div>
                    <span>{{ __('ui.footer_trust') }}</span>
                </div>
            </div>
        </footer>
    @endif

    @livewireScripts
    @stack('scripts')
</body>
</html>
