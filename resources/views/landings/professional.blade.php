{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Split Studio · design-system: design.md · designed-as-app
     Díptico: cada afirmación con su prueba enfrente, alternando lado.
     Sin gradiente, sin emoji, sin ✓/✗ como iconografía. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

@php $heroPair = $examples->first(); @endphp

{{-- Fold — mitad texto, mitad prueba. El gutter es el divisor. --}}
<section class="bg-champagne-dark border-b border-pmf-border">
    <div class="max-w-6xl mx-auto px-6 py-16 grid gap-10 lg:grid-cols-2 lg:items-center">
        <div class="max-w-md">
            <p class="text-[11px] font-semibold tracking-[0.2em] uppercase text-burgundy mb-3">
                {{ __('ui.lp_professional_badge') }}
            </p>
            <h1 class="font-serif text-4xl sm:text-5xl font-bold text-pmf-text leading-[1.1] tracking-tight">
                {{ __('ui.lp_professional_h1') }}
            </h1>
            <p class="text-lg text-pmf-secondary leading-relaxed mt-4">
                {{ __('ui.lp_professional_subtitle') }}
            </p>
            <a href="{{ locale_route('create.index', ['style' => 'linkedin_photo']) }}"
               class="inline-block mt-7 border-2 border-burgundy text-burgundy font-bold text-sm px-7 py-3 rounded-lg hover:bg-burgundy hover:text-white transition-colors duration-200 tracking-wide whitespace-nowrap">
                {{ __('ui.lp_professional_cta') }}
            </a>
            <p class="text-xs text-pmf-muted mt-3">{{ __('ui.hero_no_account') }}</p>
        </div>
        <div>
            @if($heroPair)
                <x-before-after :example="$heroPair" :eager="true" ratio="aspect-[4/5]" />
            @endif
        </div>
    </div>
</section>

{{-- Módulos alternos: texto izquierda / prueba derecha, y al revés. --}}
@php
    $benefits = [
        ['t' => __('ui.lp_professional_b1_title'), 'd' => __('ui.lp_professional_b1_desc')],
        ['t' => __('ui.lp_professional_b2_title'), 'd' => __('ui.lp_professional_b2_desc')],
        ['t' => __('ui.lp_professional_b3_title'), 'd' => __('ui.lp_professional_b3_desc')],
    ];
    $proof = $examples->slice(1)->values();
@endphp

<section class="py-16 px-6">
    <div class="max-w-6xl mx-auto">
        <h2 class="font-serif text-2xl font-bold text-pmf-text mb-10 max-w-md">
            {{ __('ui.lp_professional_benefits_title') }}
        </h2>
        <div class="space-y-14">
            @foreach($benefits as $i => $b)
                @php $pair = $proof->get($i); @endphp
                <div class="grid gap-8 md:grid-cols-2 md:items-center">
                    {{-- El texto alterna de lado; la prueba se ancla enfrente. --}}
                    <div class="max-w-sm {{ $i % 2 === 1 ? 'md:order-2 md:ml-auto' : '' }}">
                        <h3 class="font-serif text-xl font-bold text-pmf-text">{{ $b['t'] }}</h3>
                        <p class="text-sm text-pmf-secondary leading-relaxed mt-2">{{ $b['d'] }}</p>
                    </div>
                    <div class="{{ $i % 2 === 1 ? 'md:order-1' : '' }}">
                        @if($pair)
                            <x-before-after :example="$pair" ratio="aspect-[3/2]" />
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- El díptico literal: la comparación ES la forma de esta página. --}}
<section class="py-16 px-6 bg-champagne-dark border-y border-pmf-border">
    <div class="max-w-4xl mx-auto">
        <h2 class="font-serif text-2xl font-bold text-pmf-text mb-8 max-w-md">
            {{ __('ui.lp_professional_compare_title') }}
        </h2>
        <div class="grid md:grid-cols-2 gap-10">
            <div>
                <h3 class="font-serif text-base font-bold text-pmf-muted">{{ __('ui.lp_professional_compare_photographer') }}</h3>
                <p class="font-serif text-3xl font-bold text-pmf-muted mt-2 tabular-nums">{{ __('ui.lp_professional_compare_price') }}</p>
                <ul class="mt-5 space-y-2.5 text-sm text-pmf-secondary">
                    @foreach(['cp1', 'cp2', 'cp3', 'cp4'] as $k)
                        <li class="pl-4 border-l-2 border-pmf-border">{{ __("ui.lp_professional_{$k}") }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="flex items-baseline gap-3">
                    <h3 class="font-serif text-base font-bold text-burgundy">GenPic</h3>
                    <span class="text-[10px] font-bold tracking-widest uppercase text-burgundy">{{ __('ui.lp_professional_recommended') }}</span>
                </div>
                <p class="font-serif text-3xl font-bold text-burgundy mt-2 tabular-nums">{{ __('ui.lp_professional_price') }}</p>
                <ul class="mt-5 space-y-2.5 text-sm text-pmf-text">
                    @foreach(['cg1', 'cg2', 'cg3', 'cg4'] as $k)
                        <li class="pl-4 border-l-2 border-burgundy">{{ __("ui.lp_professional_{$k}") }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Proceso, mitad y mitad. --}}
<section class="py-16 px-6">
    <div class="max-w-6xl mx-auto grid gap-10 md:grid-cols-2">
        <h2 class="font-serif text-2xl font-bold text-pmf-text max-w-xs">{{ __('ui.lp_professional_how_title') }}</h2>
        <ol class="space-y-6">
            @foreach([1, 2, 3] as $n)
                <li class="flex gap-4">
                    <span class="font-serif text-sm font-bold text-burgundy tabular-nums pt-0.5 shrink-0">{{ sprintf('%02d', $n) }}</span>
                    <div class="min-w-0">
                        <h3 class="font-serif text-base font-bold text-pmf-text">{{ __("ui.lp_professional_s{$n}_title") }}</h3>
                        <p class="text-sm text-pmf-secondary leading-relaxed mt-0.5">{{ __("ui.lp_professional_s{$n}_desc") }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

@if($styles->isNotEmpty())
<section class="py-14 px-6 border-t border-pmf-border">
    <div class="max-w-6xl mx-auto grid gap-8 md:grid-cols-2 md:items-start">
        <div class="max-w-sm">
            <h2 class="font-serif text-2xl font-bold text-pmf-text">{{ __('ui.lp_professional_styles_title') }}</h2>
            <p class="text-sm text-pmf-secondary mt-2">{{ __('ui.lp_professional_styles_desc') }}</p>
        </div>
        <ul class="grid grid-cols-3 gap-4">
            @foreach($styles->take(3) as $style)
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

{{-- Prueba social. Se mantiene por decisión del propietario pese a no tener
     fuente (ver design.md § Deuda conocida). Queda a tamaño de nota al pie:
     no se le da peso de titular. --}}
<section class="py-8 px-6">
    <p class="max-w-4xl mx-auto text-xs text-pmf-muted">{{ __('ui.lp_professional_social_proof') }}</p>
</section>

<section class="py-16 px-6 bg-burgundy">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-3xl font-bold text-white leading-tight">{{ __('ui.lp_professional_cta_title') }}</h2>
        <p class="text-base text-white/80 mt-3">{{ __('ui.lp_professional_cta_desc') }}</p>
        <a href="{{ locale_route('create.index', ['style' => 'linkedin_photo']) }}"
           class="inline-block mt-6 bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_professional_cta') }}
        </a>
    </div>
</section>
@endsection
