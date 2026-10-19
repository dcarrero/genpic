{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Photographic · design-system: design.md · designed-as-app
     La transformación ES el argumento: fotografía a sangre, texto como anotación.
     El titular baja a pie de foto — en Photographic el display nunca es pancarta. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

@php $hero = $examples->first(); @endphp

{{-- Fold 1 — fotografía a sangre. El borde de la imagen es el divisor; no hay filete. --}}
<section class="relative bg-pmf-dark">
    @if($hero)
        <picture>
            <source srcset="{{ asset('img/gallery/result_' . $hero->id . '.webp') }}" type="image/webp">
            <img
                src="{{ asset('img/gallery/result_' . $hero->id . '.jpg') }}"
                alt="{{ __('ui.lp_painting_h1') }}"
                class="w-full h-[58vh] min-h-[380px] max-h-[680px] object-cover object-top"
                fetchpriority="high"
                decoding="async"
            >
        </picture>
    @else
        <div class="w-full h-[42vh] min-h-[300px]"></div>
    @endif

    {{-- Scrim con tokens, no con rgba() a mano. Va de pmf-dark sólido a transparente:
         el texto se apoya en la parte opaca, no en el píxel crudo de la foto —
         hay retratos de fondo claro y el contraste no puede depender de cuál toque. --}}
    <div class="absolute inset-x-0 bottom-0 pt-32 pb-8 bg-gradient-to-t from-pmf-dark via-pmf-dark/85 to-transparent">
        <div class="max-w-6xl mx-auto px-6">
            <p class="text-[11px] font-semibold tracking-[0.2em] uppercase text-champagne/70 mb-2">
                {{ __('ui.lp_painting_badge') }}
            </p>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-champagne max-w-xl leading-snug">
                {{ __('ui.lp_painting_h1') }}
            </h1>
            <a href="{{ locale_route('create.index') }}"
               class="inline-block mt-4 font-serif text-base text-champagne underline underline-offset-4 decoration-1 hover:decoration-2 whitespace-nowrap">
                {{ __('ui.lp_painting_cta') }}
            </a>
        </div>
    </div>
</section>

{{-- Banda de texto estrecha entre bandas de imagen. --}}
<section class="py-14 px-6">
    <p class="max-w-xl mx-auto font-serif text-xl text-pmf-text leading-relaxed">
        {{ __('ui.lp_painting_subtitle') }}
    </p>
</section>

@if($examples->count() > 1)
<section class="pb-14 px-6">
    <div class="max-w-6xl mx-auto grid gap-8 md:grid-cols-2">
        @foreach($examples->slice(1, 2) as $example)
            <x-before-after :example="$example" ratio="aspect-[4/5]" />
        @endforeach
    </div>
</section>
@endif

{{-- Anotación: el proceso en prosa, alineado a la izquierda. --}}
<section class="py-14 px-6 bg-champagne-dark">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-2xl font-bold text-pmf-text mb-6">{{ __('ui.lp_painting_how_title') }}</h2>
        <dl class="space-y-5">
            @foreach([
                ['t' => __('ui.lp_painting_s1_title'), 'd' => __('ui.lp_painting_s1_desc')],
                ['t' => __('ui.lp_painting_s2_title'), 'd' => __('ui.lp_painting_s2_desc')],
                ['t' => __('ui.lp_painting_s3_title'), 'd' => __('ui.lp_painting_s3_desc')],
            ] as $s)
                <div>
                    <dt class="font-serif text-base font-bold text-burgundy">{{ $s['t'] }}</dt>
                    <dd class="text-sm text-pmf-secondary leading-relaxed mt-0.5">{{ $s['d'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

@if($examples->count() > 3)
<section class="py-14 px-6">
    <div class="max-w-6xl mx-auto grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($examples->slice(3) as $example)
            <x-before-after :example="$example" ratio="aspect-square" />
        @endforeach
    </div>
</section>
@endif

{{-- Estilos como tira de especímenes, no rejilla de tarjetas con sombra. --}}
@if($styles->isNotEmpty())
<section class="py-14 px-6 border-t border-pmf-border">
    <div class="max-w-6xl mx-auto">
        <h2 class="font-serif text-2xl font-bold text-pmf-text">{{ __('ui.lp_painting_styles_title') }}</h2>
        <p class="text-sm text-pmf-secondary mt-1 max-w-lg">{{ __('ui.lp_painting_styles_desc') }}</p>
        <ul class="mt-8 grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($styles->take(6) as $style)
                <li>
                    <a href="{{ locale_route('create.index', ['style' => $style->style_key]) }}" class="block group">
                        <div class="aspect-square overflow-hidden rounded bg-champagne-dark">
                            @if(file_exists(public_path("img/styles/{$style->style_key}_thumb.jpg")))
                                <picture>
                                    <source srcset="{{ asset("img/styles/{$style->style_key}_thumb.webp") }}" type="image/webp">
                                    <img src="{{ asset("img/styles/{$style->style_key}_thumb.jpg") }}"
                                         alt="{{ $style->t('label') }}"
                                         class="w-full h-full object-cover"
                                         loading="lazy" decoding="async">
                                </picture>
                            @endif
                        </div>
                        <p class="mt-1.5 font-serif text-xs text-pmf-text group-hover:text-burgundy transition-colors duration-200">
                            {{ $style->t('label') }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
        <a href="{{ locale_route('styles.index') }}"
           class="inline-block mt-6 font-serif text-sm text-burgundy underline underline-offset-4 hover:decoration-2 whitespace-nowrap">
            {{ __('ui.lp_painting_see_all') }}
        </a>
    </div>
</section>
@endif

<section class="py-16 px-6 bg-burgundy">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-3xl font-bold text-white leading-tight">{{ __('ui.lp_painting_cta_title') }}</h2>
        <p class="text-base text-white/80 mt-3">{{ __('ui.lp_painting_cta_desc') }}</p>
        <a href="{{ locale_route('create.index') }}"
           class="inline-block mt-6 bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_painting_cta') }}
        </a>
    </div>
</section>
@endsection
