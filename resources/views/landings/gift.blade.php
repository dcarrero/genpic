{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Catalogue · design-system: design.md · designed-as-app
     Índice de ocasiones: el inventario ES la página. Cabecera de marca, sin display grande.
     Cada ocasión enlaza a su estilo — el enlace vive en la ficha, no en un CTA global. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

{{-- Cabecera de catálogo: marca, recuento, sin pancarta. --}}
<section class="px-6 pt-12 pb-8 border-b-2 border-pmf-dark">
    <div class="max-w-6xl mx-auto flex flex-wrap items-baseline justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-pmf-text tracking-tight">
                {{ __('ui.lp_gift_h1') }}
            </h1>
            <p class="text-sm text-pmf-secondary mt-1.5 max-w-lg">{{ __('ui.lp_gift_subtitle') }}</p>
        </div>
        <p class="text-[11px] font-semibold tracking-[0.2em] uppercase text-pmf-muted tabular-nums">
            {{ __('ui.lp_gift_badge') }}
        </p>
    </div>
</section>

{{-- El inventario. Fichas de tamaño idéntico: en Catalogue todo pesa lo mismo. --}}
<section class="px-6 py-10">
    <div class="max-w-6xl mx-auto">
        <h2 class="font-serif text-lg font-bold text-pmf-text mb-5">{{ __('ui.lp_gift_occasions_title') }}</h2>
        {{-- Las ocasiones enlazan al selector, no a un estilo concreto: $styles viene
             de shuffle(), así que "Navidad" apuntaba a un estilo distinto en cada
             carga. Una ocasión no es un estilo — que elija el usuario. --}}
        <ul class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-pmf-border border border-pmf-border">
            @foreach(['o1', 'o2', 'o3', 'o4', 'o5', 'o6', 'o7', 'o8'] as $i => $k)
                <li class="bg-champagne">
                    <a href="{{ locale_route('create.index') }}"
                       class="block p-4 h-full hover:bg-champagne-dark transition-colors duration-200">
                        <span class="block font-serif text-xs font-bold tabular-nums text-burgundy">
                            {{ sprintf('%02d', $i + 1) }}
                        </span>
                        <span class="block font-serif text-sm font-semibold text-pmf-text mt-1 min-w-0">
                            {{ __("ui.lp_gift_{$k}") }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- Especímenes del inventario real. --}}
@if($examples->isNotEmpty())
<section class="px-6 pb-10">
    <div class="max-w-6xl mx-auto grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($examples as $i => $example)
            <x-before-after :example="$example" :eager="$i === 0" ratio="aspect-square" />
        @endforeach
    </div>
</section>
@endif

{{-- Los estilos: el otro eje del catálogo. --}}
@if($styles->isNotEmpty())
<section class="px-6 py-10 border-t border-pmf-border">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-wrap items-baseline justify-between gap-3 mb-5">
            <h2 class="font-serif text-lg font-bold text-pmf-text">{{ __('ui.lp_gift_styles_title') }}</h2>
            <a href="{{ locale_route('styles.index') }}"
               class="font-serif text-sm text-burgundy underline underline-offset-4 hover:decoration-2 whitespace-nowrap">
                {{ __('ui.lp_gift_see_all') }}
            </a>
        </div>
        <ul class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            @foreach($styles->take(8) as $style)
                <li>
                    <a href="{{ locale_route('create.index', ['style' => $style->style_key]) }}" class="block group">
                        <div class="aspect-square overflow-hidden rounded bg-champagne-dark">
                            @if(file_exists(public_path("img/styles/{$style->style_key}_thumb.jpg")))
                                <picture>
                                    <source srcset="{{ asset("img/styles/{$style->style_key}_thumb.webp") }}" type="image/webp">
                                    <img src="{{ asset("img/styles/{$style->style_key}_thumb.jpg") }}"
                                         alt="{{ $style->t('label') }}" class="w-full h-full object-cover"
                                         loading="lazy" decoding="async">
                                </picture>
                            @endif
                        </div>
                        <p class="mt-1.5 font-serif text-[11px] text-pmf-text group-hover:text-burgundy transition-colors duration-200 min-w-0 truncate">
                            {{ $style->t('label') }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

{{-- Por qué, en lista seca al pie del catálogo. --}}
<section class="px-6 py-10 bg-champagne-dark border-t border-pmf-border">
    <div class="max-w-6xl mx-auto grid gap-6 sm:grid-cols-3">
        @foreach([1, 2, 3] as $n)
            <div>
                <h3 class="font-serif text-base font-bold text-pmf-text">{{ __("ui.lp_gift_w{$n}_title") }}</h3>
                <p class="text-sm text-pmf-secondary leading-relaxed mt-1">{{ __("ui.lp_gift_w{$n}_desc") }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="py-16 px-6 bg-burgundy">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-3xl font-bold text-white leading-tight">{{ __('ui.lp_gift_cta_title') }}</h2>
        <p class="text-base text-white/80 mt-3">{{ __('ui.lp_gift_cta_desc') }}</p>
        <a href="{{ locale_route('create.index') }}"
           class="inline-block mt-6 bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_gift_cta') }}
        </a>
    </div>
</section>
@endsection
