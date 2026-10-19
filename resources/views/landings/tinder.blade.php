{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Marquee Hero · design-system: design.md · designed-as-app
     El titular llena el pliegue. Sin CTA arriba: el primero llega debajo del filete.
     El rosa #FD3A73 (marca de Tinder) retirado — no es nuestro acento y sugería afiliación. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

{{-- Fold — tipografía como único visual. Sin botón, sin imagen. --}}
<section class="px-6 py-20 sm:py-28 bg-pmf-dark">
    <div class="max-w-6xl mx-auto">
        {{-- Excepción Marquee de design.md: el display sube a 7xl en desktop.
             overflow-wrap protege el escalón móvil. --}}
        <h1 class="font-serif text-4xl sm:text-6xl lg:text-7xl font-bold text-champagne leading-[0.98] tracking-tight max-w-4xl [overflow-wrap:anywhere]">
            {{ __('ui.lp_tinder_h1') }}
        </h1>
    </div>
</section>

{{-- Cambio duro de color: el divisor de esta forma. Debajo, la página es otra cosa. --}}
<section class="px-6 py-12 border-b border-pmf-border">
    <div class="max-w-6xl mx-auto grid gap-8 md:grid-cols-12 md:items-end">
        {{-- El badge no cabe en el pliegue de un Marquee (solo display, sin subhead),
             pero su copy es real: baja aquí en vez de perderse. --}}
        <div class="md:col-span-7 max-w-xl">
            <p class="font-serif text-sm text-burgundy">{{ __('ui.lp_tinder_badge') }}</p>
            <p class="font-serif text-xl text-pmf-text leading-relaxed mt-1.5">
                {{ __('ui.lp_tinder_subtitle') }}
            </p>
        </div>
        <div class="md:col-span-5 md:text-right">
            {{-- Primer CTA de la página, ya fuera del pliegue. --}}
            <a href="{{ locale_route('create.index', ['style' => 'tinder_casual']) }}"
               class="inline-block bg-burgundy text-white font-bold text-base px-8 py-4 rounded-lg hover:bg-burgundy-dark transition-colors duration-200 tracking-wide whitespace-nowrap">
                {{ __('ui.lp_tinder_cta') }}
            </a>
            <p class="text-xs text-pmf-muted mt-2">{{ __('ui.hero_no_account') }}</p>
        </div>
    </div>
</section>

{{-- Debajo del pliegue: la lista. --}}
@if($examples->isNotEmpty())
<section class="px-6 py-14">
    <div class="max-w-6xl mx-auto grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($examples as $example)
            <x-before-after :example="$example" ratio="aspect-[4/5]" />
        @endforeach
    </div>
</section>
@endif

<section class="px-6 py-14 bg-champagne-dark border-y border-pmf-border">
    <div class="max-w-6xl mx-auto grid gap-8 md:grid-cols-12">
        <h2 class="md:col-span-4 font-serif text-2xl font-bold text-pmf-text">{{ __('ui.lp_tinder_benefits_title') }}</h2>
        <dl class="md:col-span-8 grid gap-6 sm:grid-cols-3">
            @foreach([1, 2, 3] as $n)
                <div class="border-t-2 border-burgundy pt-3">
                    <dt class="font-serif text-base font-bold text-pmf-text">{{ __("ui.lp_tinder_b{$n}_title") }}</dt>
                    <dd class="text-sm text-pmf-secondary leading-relaxed mt-1">{{ __("ui.lp_tinder_b{$n}_desc") }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

{{-- Cifras: mantenidas por decisión del propietario (design.md § Deuda conocida),
     a tamaño de nota. --}}
<section class="px-6 py-8">
    <ul class="max-w-6xl mx-auto flex flex-wrap gap-x-8 gap-y-2 text-xs text-pmf-muted">
        @foreach([1, 2, 3] as $n)
            <li>
                <span class="font-semibold text-pmf-secondary tabular-nums">{{ __("ui.lp_tinder_stat{$n}_num") }}</span>
                {{ __("ui.lp_tinder_stat{$n}_label") }}
            </li>
        @endforeach
    </ul>
</section>

@if($styles->isNotEmpty())
<section class="px-6 pb-14">
    <div class="max-w-6xl mx-auto">
        <h2 class="font-serif text-2xl font-bold text-pmf-text mb-6">{{ __('ui.lp_tinder_styles_title') }}</h2>
        <ul class="grid grid-cols-3 sm:grid-cols-6 gap-4">
            @foreach($styles->take(6) as $style)
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
                        <p class="mt-1.5 font-serif text-xs text-pmf-text group-hover:text-burgundy transition-colors duration-200">{{ $style->t('label') }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="py-16 px-6 bg-burgundy">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-3xl font-bold text-white leading-tight">{{ __('ui.lp_tinder_cta_title') }}</h2>
        <p class="text-base text-white/80 mt-3">{{ __('ui.lp_tinder_cta_desc') }}</p>
        <a href="{{ locale_route('create.index', ['style' => 'tinder_casual']) }}"
           class="inline-block mt-6 bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_tinder_cta') }}
        </a>
    </div>
</section>
@endsection
