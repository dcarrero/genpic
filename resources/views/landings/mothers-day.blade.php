{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Manifesto · design-system: design.md · designed-as-app
     Declaración antes que producto. Bloques a sangre como divisor — sin filetes.
     Sin inclinación de -2°/-4°: en esta marca chirría. Se usa la otra opción de la
     spec, el bloque de acento sobre la frase. Copy nuevo (lp_mothers_manifesto_*). --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

{{-- Pliegue: la declaración. Nada más. --}}
<section class="px-6 py-20 sm:py-24 bg-pmf-dark">
    <div class="max-w-5xl mx-auto">
        <p class="text-[11px] font-semibold tracking-[0.2em] uppercase text-champagne/60 mb-8">
            {{ __('ui.lp_mothers_badge') }}
        </p>
        <h1 class="font-serif font-bold uppercase leading-[0.95] tracking-tight [overflow-wrap:anywhere]">
            <span class="block text-3xl sm:text-5xl text-champagne">
                {{ __('ui.lp_mothers_manifesto_1') }}
            </span>
            {{-- El bloque de acento cae sobre la frase que gira el argumento. --}}
            <span class="inline-block mt-3 text-3xl sm:text-5xl text-white bg-burgundy px-3 py-1">
                {{ __('ui.lp_mothers_manifesto_2') }}
            </span>
        </h1>
    </div>
</section>

{{-- Afirmaciones. Una por párrafo, grandes: se leen desde el otro lado del salón. --}}
<section class="px-6 py-16 bg-champagne">
    <div class="max-w-3xl mx-auto space-y-8">
        <p class="font-serif text-xl sm:text-2xl text-pmf-text leading-snug">{{ __('ui.lp_mothers_claim_1') }}</p>
        <p class="font-serif text-xl sm:text-2xl text-pmf-text leading-snug">{{ __('ui.lp_mothers_claim_2') }}</p>
        <p class="font-serif text-2xl sm:text-3xl font-bold text-burgundy leading-snug">{{ __('ui.lp_mothers_claim_3') }}</p>
    </div>
</section>

{{-- Bloque a sangre: la fotografía es el divisor. --}}
@if($examples->first())
<section class="bg-pmf-dark">
    <div class="max-w-5xl mx-auto px-6 py-14">
        <x-before-after :example="$examples->first()" :eager="true" ratio="aspect-[3/2]" surface="dark" />
    </div>
</section>
@endif

{{-- Lo que era el hero original, ahora como afirmación de cuerpo. --}}
<section class="px-6 py-16 bg-champagne-dark">
    <div class="max-w-3xl mx-auto">
        <h2 class="font-serif text-2xl sm:text-3xl font-bold text-pmf-text leading-snug">{{ __('ui.lp_mothers_h1') }}</h2>
        <p class="font-serif text-lg text-pmf-secondary leading-relaxed mt-4">{{ __('ui.lp_mothers_subtitle') }}</p>
    </div>
</section>

{{-- Las ideas: afirmaciones, no tarjetas con emoji. --}}
<section class="px-6 py-16 bg-champagne">
    <div class="max-w-3xl mx-auto">
        <h2 class="font-serif text-lg font-bold text-pmf-text mb-6">{{ __('ui.lp_mothers_ideas_title') }}</h2>
        <dl class="space-y-6">
            @foreach([1, 2, 3] as $n)
                <div class="border-l-4 border-burgundy pl-5">
                    <dt class="font-serif text-lg font-bold text-pmf-text">{{ __("ui.lp_mothers_i{$n}_title") }}</dt>
                    <dd class="font-serif text-base text-pmf-secondary leading-relaxed mt-1">{{ __("ui.lp_mothers_i{$n}_desc") }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

@if($examples->count() > 1)
<section class="bg-pmf-dark">
    <div class="max-w-5xl mx-auto px-6 py-14 grid gap-6 sm:grid-cols-3">
        @foreach($examples->slice(1) as $example)
            <x-before-after :example="$example" ratio="aspect-square" surface="dark" />
        @endforeach
    </div>
</section>
@endif

@if($styles->isNotEmpty())
<section class="px-6 py-14 bg-champagne">
    <div class="max-w-5xl mx-auto">
        <h2 class="font-serif text-lg font-bold text-pmf-text mb-5">{{ __('ui.lp_mothers_styles_title') }}</h2>
        <ul class="grid grid-cols-4 sm:grid-cols-8 gap-3">
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
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

{{-- El botón: bloque sólido, sobredimensionado, muy por debajo del pliegue. --}}
<section class="px-6 py-20 bg-burgundy">
    <div class="max-w-3xl mx-auto">
        <h2 class="font-serif text-3xl sm:text-4xl font-bold text-white leading-tight">{{ __('ui.lp_mothers_cta_title') }}</h2>
        <p class="font-serif text-lg text-white/80 mt-3">{{ __('ui.lp_mothers_cta_desc') }}</p>
        <a href="{{ locale_route('create.index') }}"
           class="block sm:inline-block text-center mt-8 bg-white text-burgundy font-bold text-lg px-10 py-5 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_mothers_cta') }}
        </a>
    </div>
</section>
@endsection
