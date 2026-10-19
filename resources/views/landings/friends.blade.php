{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Bento Grid · design-system: design.md · designed-as-app
     Bloques de peso desigual: el ritmo lo da la variación de tamaño, no la repetición
     de tarjetas idénticas. Hero de altura fija (no viewport completo).
     La irregularidad se consigue con col-span + proporción, no con alturas fijas. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

<section class="px-6 py-14 bg-champagne-dark border-b border-pmf-border">
    <div class="max-w-6xl mx-auto">
        <p class="font-serif text-sm text-burgundy mb-2">{{ __('ui.lp_friends_badge') }}</p>
        <h1 class="font-serif text-4xl sm:text-5xl font-bold text-pmf-text leading-[1.1] tracking-tight max-w-2xl [overflow-wrap:anywhere]">
            {{ __('ui.lp_friends_h1') }}
        </h1>
        <p class="text-lg text-pmf-secondary leading-relaxed mt-4 max-w-xl">
            {{ __('ui.lp_friends_subtitle') }}
        </p>
        <a href="{{ locale_route('create.index') }}"
           class="inline-block mt-6 border-2 border-burgundy text-burgundy font-bold text-sm px-7 py-3 rounded-lg hover:bg-burgundy hover:text-white transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_friends_cta') }}
        </a>
    </div>
</section>

{{-- La rejilla: vanos de 2 y 4 columnas mezclados. El gap es el divisor; sin filetes. --}}
<section class="px-6 py-12">
    <div class="max-w-6xl mx-auto grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Bloque ancho: la prueba real, 2 columnas. --}}
        @if($examples->first())
            <div class="col-span-2 bg-champagne-dark rounded-xl p-4">
                <h2 class="font-serif text-base font-bold text-pmf-text mb-3">{{ __('ui.lp_friends_occasions_title') }}</h2>
                <x-before-after :example="$examples->first()" :eager="true" ratio="aspect-[3/2]" />
            </div>
        @endif

        {{-- Cuatro ocasiones, una columna cada una. --}}
        @foreach(['o1', 'o2', 'o3', 'o4'] as $i => $k)
            <a href="{{ locale_route('create.index') }}"
               class="col-span-1 bg-burgundy-soft rounded-xl p-4 flex flex-col justify-between gap-6 hover:bg-copper/25 transition-colors duration-200 min-w-0">
                <span class="font-serif text-xs font-bold tabular-nums text-burgundy">{{ sprintf('%02d', $i + 1) }}</span>
                <span class="font-serif text-sm font-semibold text-pmf-text [overflow-wrap:anywhere]">{{ __("ui.lp_friends_{$k}") }}</span>
            </a>
        @endforeach

        {{-- Bloque a todo lo ancho: el proceso, comprimido en una línea. --}}
        <div class="col-span-2 lg:col-span-4 bg-pmf-dark rounded-xl p-5">
            <h2 class="font-serif text-sm font-bold text-champagne mb-2.5">{{ __('ui.lp_friends_how_title') }}</h2>
            <ol class="flex flex-wrap gap-x-8 gap-y-2">
                @foreach([1, 2, 3] as $n)
                    <li class="text-xs text-pmf-muted-dark min-w-0">
                        <span class="font-bold text-champagne tabular-nums">{{ $n }}</span>
                        {{ __("ui.lp_friends_s{$n}_title") }}
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Dos pares más. A col-span-1 en móvil cada mitad del díptico caía a ~52px:
             un antes/después de 52px no comunica nada. Ocupan 2 columnas hasta lg. --}}
        @foreach($examples->slice(1, 2) as $example)
            <div class="col-span-2 lg:col-span-1 bg-champagne-dark rounded-xl p-3">
                <x-before-after :example="$example" ratio="aspect-[4/5]" />
            </div>
        @endforeach

        {{-- Estilos: bloque ancho de cierre. --}}
        @if($styles->isNotEmpty())
            <div class="col-span-2 bg-champagne-dark rounded-xl p-4">
                <h2 class="font-serif text-base font-bold text-pmf-text">{{ __('ui.lp_friends_styles_title') }}</h2>
                <p class="text-xs text-pmf-secondary mt-0.5 mb-3">{{ __('ui.lp_friends_styles_desc') }}</p>
                <ul class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach($styles->take(4) as $style)
                        <li>
                            <a href="{{ locale_route('create.index', ['style' => $style->style_key]) }}"
                               class="block aspect-square rounded overflow-hidden bg-champagne">
                                @if(file_exists(public_path("img/styles/{$style->style_key}_thumb.jpg")))
                                    <picture>
                                        <source srcset="{{ asset("img/styles/{$style->style_key}_thumb.webp") }}" type="image/webp">
                                        <img src="{{ asset("img/styles/{$style->style_key}_thumb.jpg") }}"
                                             alt="{{ $style->t('label') }}" class="w-full h-full object-cover"
                                             loading="lazy" decoding="async">
                                    </picture>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ locale_route('styles.index') }}"
                   class="inline-block mt-3 font-serif text-xs text-burgundy underline underline-offset-4 hover:decoration-2 whitespace-nowrap">
                    {{ __('ui.lp_friends_see_all') }}
                </a>
            </div>
        @endif

    </div>
</section>

<section class="py-16 px-6 bg-burgundy">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-3xl font-bold text-white leading-tight">{{ __('ui.lp_friends_cta_title') }}</h2>
        <p class="text-base text-white/80 mt-3">{{ __('ui.lp_friends_cta_desc') }}</p>
        <a href="{{ locale_route('create.index') }}"
           class="inline-block mt-6 bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_friends_cta') }}
        </a>
    </div>
</section>
@endsection
