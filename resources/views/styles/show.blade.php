{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Photographic · design-system: design.md · designed-as-app
     el antes/despues es el argumento --}}
@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $label = $style->t('label');
    $desc = $style->t('description');
    $hasImage = file_exists(public_path("img/styles/{$style->style_key}.jpg"));

    // Foto original según categoría: estilos de persona sola usan original-woman, el resto original-family
    $singlePersonCategories = ['professional', 'thematic', 'adventure'];
    $originalFile = in_array($style->style_category ?? '', $singlePersonCategories) ? 'img/original-woman.jpg' : 'img/original-family.jpg';
    $hasOriginal = file_exists(public_path($originalFile));
@endphp

@section('content')
<section class="py-12 px-6">
    <div class="max-w-5xl mx-auto">
        {{-- Breadcrumb --}}
        <div class="mb-8 text-sm text-pmf-muted">
            <a href="{{ locale_route('styles.index') }}" class="hover:text-burgundy transition">{{ __('ui.nav_styles') }}</a>
            <span class="mx-2">›</span>
            <span class="text-pmf-text font-semibold">{{ $label }}</span>
        </div>

        {{-- Before/After Slider — CSS puro + input range --}}
        @if($hasImage && $hasOriginal)
        <style>
            .ba-slider { position: relative; overflow: hidden; border-radius: 1rem; max-width: 42rem; margin: 0 auto; }
            .ba-slider img { display: block; width: 100%; height: auto; }
            .ba-slider .ba-overlay { position: absolute; inset: 0; overflow: hidden; }
            .ba-slider .ba-overlay img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
            .ba-slider .ba-line { position: absolute; top: 0; bottom: 0; width: 3px; background: white; z-index: 20; box-shadow: 0 0 10px rgba(0,0,0,0.5); pointer-events: none; }
            .ba-slider .ba-handle { position: absolute; top: 50%; z-index: 30; width: 48px; height: 48px; background: white; border-radius: 50%; box-shadow: 0 2px 12px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; pointer-events: none; }
            .ba-slider:has(input[type=range]:focus-visible) .ba-handle { outline: 3px solid #7A2D3B; outline-offset: 2px; }
                .ba-slider input[type=range] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: col-resize; z-index: 40; margin: 0;
                    /* touch-action: el range cubre toda la imagen; sin esto se traga el
                       gesto vertical y el usuario queda atrapado sin poder pasar del retrato.
                       pan-y deja el scroll al navegador y solo el arrastre horizontal llega aqui. */
                    touch-action: pan-y; }
            .ba-slider .ba-label { position: absolute; top: 12px; z-index: 10; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.08em; }
        </style>
        <div class="mb-10">
            <h3 class="font-serif text-lg font-semibold text-pmf-text mb-4 text-center">
                {{ __('ui.style_slide') }}
            </h3>
            <div class="ba-slider shadow-lg" id="ba-container">
                {{-- Original (base, da tamaño) --}}
                <img src="{{ asset($originalFile) }}" alt="Original">

                {{-- Estilo (overlay, clip dinámico) --}}
                <div class="ba-overlay" id="ba-overlay" style="clip-path: inset(0 0 0 50%)">
                    <img src="{{ asset("img/styles/{$style->style_key}.jpg") }}" alt="{{ $label }}">
                </div>

                {{-- Línea --}}
                <div class="ba-line" id="ba-line" style="left: calc(50% - 1.5px)"></div>

                {{-- Handle --}}
                <div class="ba-handle" id="ba-handle" style="left: 50%; transform: translate(-50%, -50%)">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#7A2D3B" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l-3 3 3 3m8-6l3 3-3 3"/></svg>
                </div>

                {{-- Labels --}}
                <div class="ba-label" style="left:12px; background:rgba(0,0,0,0.6);color:white;">📸 Original</div>
                <div class="ba-label" style="right:12px; background:rgba(122,45,59,0.9);color:white;">🎨 {{ $label }}</div>

                {{-- Range input invisible (controla todo) --}}
                <input type="range" min="0" max="100" value="50" aria-label="{{ __('ui.a11y_compare_slider') }}" id="ba-range">
            </div>
        </div>
        <script>
            document.getElementById('ba-range').addEventListener('input', function() {
                var v = this.value;
                document.getElementById('ba-overlay').style.clipPath = 'inset(0 0 0 ' + v + '%)';
                document.getElementById('ba-line').style.left = 'calc(' + v + '% - 1.5px)';
                document.getElementById('ba-handle').style.left = v + '%';
            });
        </script>
        @endif

        <div class="max-w-2xl mx-auto">
            {{-- Info + CTA --}}
            <div class="space-y-6">
                <h1 class="font-serif text-3xl font-bold text-pmf-text">{{ $label }}</h1>
                <p class="font-serif text-pmf-secondary leading-relaxed">{{ $desc }}</p>

                {{-- Detalles del estilo para SEO --}}
                <div class="bg-white rounded-xl p-5 border border-pmf-border space-y-3">
                    <h3 class="font-serif text-sm font-semibold text-pmf-text">
                        {{ __('ui.style_about') }}
                    </h3>
                    <p class="text-xs text-pmf-secondary leading-relaxed">
                        {{ __('ui.style_about_text', ['style' => $label]) }}
                    </p>
                    <ul class="text-xs text-pmf-secondary space-y-1">
                        <li>✓ {{ __('ui.style_faces') }}</li>
                        <li>✓ {{ __('ui.style_ready') }}</li>
                        <li>✓ {{ __('ui.style_free_preview') }}</li>
                        @if(config('genpic.free_web_basic_enabled'))
                            <li>✓ {!! __('ui.free_promo_price_note') !!}</li>
                        @else
                            <li>✓ {{ __('ui.style_from_price') }}</li>
                        @endif
                    </ul>
                </div>

                {{-- Photo tip for special categories --}}
                @if(in_array($style->style_category ?? '', ['professional', 'thematic', 'adventure']))
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700 space-y-1">
                    @if($style->max_faces === 1)
                    <p class="font-semibold text-center">{{ __('ui.single_person_notice') }}</p>
                    @endif
                    <p class="text-center">{{ __("ui.photo_tip_{$style->style_category}") }}</p>
                </div>
                @endif

                <a href="{{ locale_route('create.index', ['style' => $style->style_key]) }}"
                   class="block w-full text-center bg-burgundy text-white font-bold text-base py-4 rounded-lg hover:bg-burgundy-dark transition tracking-wide">
                    {{ __('ui.style_create_cta') }}
                </a>

                <p class="text-xs text-pmf-muted text-center">
                    {{ __('ui.style_no_register') }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- Otros estilos --}}
<section class="py-12 px-6 bg-champagne-dark">
    <div class="max-w-5xl mx-auto">
        <h2 class="font-serif text-2xl font-bold text-pmf-text mb-6 text-center">
            {{ __('ui.styles_explore_more') }}
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
            @foreach($otherStyles as $other)
            @php $otherLabel = $other->t('label'); @endphp
            <a href="{{ locale_route('styles.show', $other->style_key) }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md overflow-hidden transition">
                @if(file_exists(public_path("img/styles/{$other->style_key}_thumb.jpg")))
                <picture>
                    <source srcset="{{ asset("img/styles/{$other->style_key}_thumb.webp") }}" type="image/webp">
                    <img src="{{ asset("img/styles/{$other->style_key}_thumb.jpg") }}" alt="{{ $otherLabel }}" class="w-full aspect-square object-cover group-hover:scale-105 transition-transform" loading="lazy">
                </picture>
                @else
                <div class="w-full aspect-square bg-gradient-to-br from-burgundy-soft to-champagne-dark"></div>
                @endif
                <p class="text-center py-2 text-[10px] font-semibold text-pmf-text truncate px-1">{{ $otherLabel }}</p>
            </a>
            @endforeach
        </div>
        <div class="text-center mt-6">
            <a href="{{ locale_route('styles.index') }}" class="text-burgundy font-semibold text-sm hover:underline">
                {{ __('ui.styles_see_all') }}
            </a>
        </div>
    </div>
</section>

@push('jsonld')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "GenPic",
            "item": "{{ url('/') }}"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": @json(__('ui.nav_styles')),
            "item": "{{ locale_route('styles.index') }}"
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": @json($label)
        }
    ]
}
</script>
@endpush
@endsection
