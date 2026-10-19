{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Letter · design-system: design.md · designed-as-app
     Primera persona, medida estrecha, sin botón en el pliegue. El CTA es la
     despedida al pie. Copy nuevo (lp_family_letter_*) en los 5 idiomas. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

{{-- La carta. Medida ~55ch: la prosa manda, nada compite con ella. --}}
<section class="px-6 py-16 sm:py-20">
    <div class="max-w-[36rem] mx-auto">
        <p class="font-serif text-2xl sm:text-3xl italic text-pmf-text">
            {{ __('ui.lp_family_letter_open') }}
        </p>

        {{-- h1 real para SEO y lectores de pantalla, con peso de párrafo destacado:
             en Letter el titular no grita, abre. --}}
        <h1 class="font-serif text-xl sm:text-2xl font-bold text-pmf-text leading-snug mt-6">
            {{ __('ui.lp_family_h1') }}
        </h1>

        <div class="mt-5 space-y-5 font-serif text-base text-pmf-text leading-[1.75]">
            <p>{{ __('ui.lp_family_letter_p1') }}</p>
            <p>{{ __('ui.lp_family_letter_p2') }}</p>
            <p>{{ __('ui.lp_family_letter_p3') }}</p>
        </div>

        <p class="font-serif text-sm text-pmf-secondary mt-8">{{ __('ui.lp_family_letter_sign') }}</p>
    </div>
</section>

{{-- Una fotografía tranquila, inline. En Letter la imagen acompaña, no domina. --}}
@if($examples->first())
<section class="px-6 pb-16">
    <div class="max-w-[36rem] mx-auto">
        <x-before-after :example="$examples->first()" ratio="aspect-[3/2]" />
    </div>
</section>
@endif

{{-- Separador de carta: no un filete, tres asteriscos. --}}
<div class="text-center text-pmf-muted font-serif text-sm tracking-[0.5em] pb-16" aria-hidden="true">* * *</div>

{{-- Los momentos, en prosa continua, no en tres tarjetas con emoji. --}}
<section class="px-6 pb-16">
    <div class="max-w-[36rem] mx-auto">
        <h2 class="font-serif text-lg font-bold text-pmf-text">{{ __('ui.lp_family_moments_title') }}</h2>
        <dl class="mt-4 space-y-4 font-serif text-base leading-[1.75]">
            @foreach([1, 2, 3] as $n)
                <div>
                    <dt class="inline font-bold text-burgundy">{{ __("ui.lp_family_m{$n}_title") }}.</dt>
                    <dd class="inline text-pmf-text"> {{ __("ui.lp_family_m{$n}_desc") }}</dd>
                </div>
            @endforeach
        </dl>

        <p class="mt-6 font-serif text-base text-pmf-text leading-[1.75]">
            {{ __('ui.lp_family_gift_title') }}
            @foreach([1, 2, 3] as $n)
                <span class="text-pmf-secondary">{{ __("ui.lp_family_g{$n}") }}@if($n < 3) · @endif</span>
            @endforeach
        </p>
    </div>
</section>

{{-- Más fotografías, aún dentro de la medida de la carta. --}}
@if($examples->count() > 1)
<section class="px-6 pb-16">
    <div class="max-w-[36rem] mx-auto grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach($examples->slice(1, 2) as $example)
            <x-before-after :example="$example" ratio="aspect-square" />
        @endforeach
    </div>
</section>
@endif

{{-- Los estilos, mencionados de pasada. --}}
@if($styles->isNotEmpty())
<section class="px-6 pb-16">
    <div class="max-w-[36rem] mx-auto">
        <h2 class="font-serif text-lg font-bold text-pmf-text">{{ __('ui.lp_family_styles_title') }}</h2>
        <p class="font-serif text-base text-pmf-text leading-[1.75] mt-2">{{ __('ui.lp_family_styles_desc') }}</p>
        <ul class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach($styles->take(4) as $style)
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
                        <p class="mt-1 font-serif text-[11px] text-pmf-text group-hover:text-burgundy transition-colors duration-200 truncate">{{ $style->t('label') }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
        <a href="{{ locale_route('styles.index') }}"
           class="inline-block mt-4 font-serif text-sm text-burgundy underline underline-offset-4 hover:decoration-2 whitespace-nowrap">
            {{ __('ui.lp_family_see_all') }}
        </a>
    </div>
</section>
@endif

{{-- La posdata ES el cierre de una carta. El botón llega aquí, no antes. --}}
<section class="px-6 pb-20">
    <div class="max-w-[36rem] mx-auto border-t border-pmf-border pt-8">
        <h2 class="font-serif text-xl font-bold text-pmf-text">{{ __('ui.lp_family_cta_title') }}</h2>
        <p class="font-serif text-base text-pmf-text leading-[1.75] mt-2">{{ __('ui.lp_family_cta_desc') }}</p>
        <a href="{{ locale_route('create.index') }}"
           class="inline-block mt-5 bg-burgundy text-white font-bold text-base px-8 py-4 rounded-lg hover:bg-burgundy-dark transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_family_cta') }}
        </a>
        <p class="font-serif text-sm italic text-pmf-secondary mt-6">{{ __('ui.lp_family_letter_ps') }}</p>
    </div>
</section>
@endsection
