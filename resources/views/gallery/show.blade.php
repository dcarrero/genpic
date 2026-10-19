{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Photographic · design-system: design.md · designed-as-app
     un par a pantalla --}}
@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $styleName = $example->artisticStyle->t('label');
    $typeLabel = $photoTypes[$example->photo_type][$locale] ?? $example->photo_type;
    $resultUrl = asset('img/gallery/result_' . $example->id . '.jpg');
    $originalUrl = asset('img/gallery/original_' . $example->id . '.jpg');
@endphp

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <div class="mb-6">
        <a href="{{ locale_route('gallery.index') }}" class="text-sm text-burgundy hover:underline">
            ← {{ __('ui.gallery_back') }}
        </a>
    </div>

    {{-- Slider: original (izquierda) vs estilo (derecha) --}}
    <style>
        .gd-slider { position: relative; overflow: hidden; border-radius: 1rem; }
        .gd-slider img.gd-base { display: block; width: 100%; height: auto; }
        .gd-slider .gd-over { position: absolute; inset: 0; overflow: hidden; }
        .gd-slider .gd-over img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .gd-slider .gd-ln { position: absolute; top: 0; bottom: 0; width: 3px; background: white; z-index: 10; box-shadow: 0 0 12px rgba(0,0,0,0.4); pointer-events: none; }
        .gd-slider .gd-hd { position: absolute; top: 50%; z-index: 20; pointer-events: none; }
        /* El range es opacity:0, así que su anillo de foco es invisible: se
           traslada al handle, que sí se ve. */
        .gd-slider:has(input[type=range]:focus-visible) .gd-hd { outline: 3px solid #7A2D3B; outline-offset: 2px; border-radius: 9999px; }
        .gd-slider input[type=range] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: col-resize; z-index: 30; margin: 0;
            /* touch-action: el range cubre toda la imagen; sin esto se traga el
               gesto vertical y el usuario queda atrapado sin poder pasar del
               retrato. pan-y deja el scroll al navegador y solo el arrastre
               horizontal llega aqui. */
            touch-action: pan-y; }
        .gd-slider .gd-lb { position: absolute; top: 12px; z-index: 10; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 8px; color: white; pointer-events: none; }
    </style>

    <div class="bg-white rounded-2xl overflow-hidden shadow-lg border border-pmf-border">
        <div class="gd-slider" id="gds">
            {{-- Original = base (define tamaño natural) --}}
            <img src="{{ $originalUrl }}" alt="Original" class="gd-base">

            {{-- Estilo = overlay (clip desde la derecha, empieza al 50%) --}}
            <div class="gd-over" id="gd-ov" style="clip-path: inset(0 0 0 50%)">
                <img src="{{ $resultUrl }}" alt="{{ $styleName }}">
            </div>

            <div class="gd-ln" id="gd-ln" style="left: calc(50% - 1.5px)"></div>
            <div class="gd-hd" id="gd-hd" style="left: 50%; transform: translate(-50%, -50%)">
                <div style="width:48px;height:48px;background:white;border-radius:50%;box-shadow:0 2px 12px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#7A2D3B" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l-3 3 3 3m8-6l3 3-3 3"/></svg>
                </div>
            </div>

            <span class="gd-lb" style="left:12px; background:rgba(0,0,0,0.6);">📸 Original</span>
            <span class="gd-lb" style="right:12px; background:rgba(122,45,59,0.9);">🎨 {{ $styleName }}</span>

            <input type="range" min="0" max="100" value="50" aria-label="{{ __('ui.a11y_compare_slider') }}" id="gd-rng">
        </div>
    </div>

    <script>
    document.getElementById('gd-rng').addEventListener('input', function() {
        var v = this.value;
        document.getElementById('gd-ov').style.clipPath = 'inset(0 0 0 ' + v + '%)';
        document.getElementById('gd-ln').style.left = 'calc(' + v + '% - 1.5px)';
        document.getElementById('gd-hd').style.left = v + '%';
    });
    </script>

    {{-- Info + CTA --}}
    <div class="mt-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-pmf-text truncate">{{ $styleName }}</h1>
            <div class="flex items-center gap-3 mt-2">
                <span class="text-xs text-pmf-muted bg-pmf-border/50 px-3 py-1 rounded-full">{{ $typeLabel }}</span>
                <span class="text-xs text-pmf-muted">{{ __('ui.gallery_slide_hint') }}</span>
            </div>
        </div>

        <a
            href="{{ locale_route('create.index') }}?style={{ $example->style_key }}"
            class="shrink-0 bg-burgundy text-white font-bold py-3.5 px-8 rounded-lg hover:bg-burgundy-dark transition text-sm tracking-wide"
        >
            {{ __('ui.gallery_try_style') }}
        </a>
    </div>

    {{-- Descripción del estilo --}}
    @php
        $styleDesc = $example->artisticStyle->t('description');
    @endphp
    @if($styleDesc)
    <div class="mt-6 bg-champagne-dark rounded-xl p-6">
        <p class="font-serif text-pmf-secondary leading-relaxed">{{ $styleDesc }}</p>
    </div>
    @endif

    {{-- Más ejemplos --}}
    <div class="mt-12 text-center">
        <a href="{{ locale_route('gallery.index') }}" class="text-burgundy font-semibold hover:underline">
            {{ __('ui.gallery_see_all') }} →
        </a>
    </div>

</div>

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
            "name": @json(__('ui.nav_gallery')),
            "item": "{{ locale_route('gallery.index') }}"
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": @json($styleName)
        }
    ]
}
</script>
@endpush
@endsection
