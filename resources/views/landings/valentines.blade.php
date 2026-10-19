{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Feature Stack · design-system: design.md · designed-as-app
     Panel fijo a la izquierda + pares de pareja desfilando a la derecha.
     Quote-Led descartada: los testimonios del seeder están inventados (design.md).
     El sticky se apaga en móvil — un panel fijo en 375px se come el pliegue. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

<section class="px-6 pt-14 pb-10 border-b border-pmf-border">
    <div class="max-w-6xl mx-auto">
        <p class="font-serif text-sm text-burgundy mb-2">{{ __('ui.lp_valentines_badge') }}</p>
        <h1 class="font-serif text-4xl sm:text-5xl font-bold text-pmf-text leading-[1.1] tracking-tight max-w-2xl [overflow-wrap:anywhere]">
            {{ __('ui.lp_valentines_h1') }}
        </h1>
        <p class="text-lg text-pmf-secondary leading-relaxed mt-4 max-w-xl">
            {{ __('ui.lp_valentines_subtitle') }}
        </p>
        <a href="{{ locale_route('create.index', ['style' => 'valentines']) }}"
           class="inline-block mt-6 border-2 border-burgundy text-burgundy font-bold text-sm px-7 py-3 rounded-lg hover:bg-burgundy hover:text-white transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_valentines_cta') }}
        </a>
        <p class="text-xs text-pmf-muted mt-3">{{ __('ui.hero_no_account') }}</p>
    </div>
</section>

{{-- El stack: panel fijo + columna que desfila. --}}
<section class="px-6 py-14">
    <div class="max-w-6xl mx-auto grid gap-10 lg:grid-cols-12">

        {{-- Panel. sticky solo desde lg: en móvil es un encabezado normal. --}}
        <aside class="lg:col-span-4">
            <div class="lg:sticky lg:top-8">
                <h2 class="font-serif text-2xl font-bold text-pmf-text">{{ __('ui.lp_valentines_for_title') }}</h2>
                <dl class="mt-5 space-y-4">
                    @foreach([1, 2, 3] as $n)
                        <div class="border-l-2 border-burgundy pl-4">
                            <dt class="font-serif text-base font-bold text-pmf-text">{{ __("ui.lp_valentines_f{$n}_title") }}</dt>
                            <dd class="text-sm text-pmf-secondary leading-relaxed mt-0.5">{{ __("ui.lp_valentines_f{$n}_desc") }}</dd>
                        </div>
                    @endforeach
                </dl>
                <a href="{{ locale_route('create.index', ['style' => 'valentines']) }}"
                   class="inline-block mt-6 font-serif text-sm text-burgundy underline underline-offset-4 hover:decoration-2 whitespace-nowrap">
                    {{ __('ui.lp_valentines_try') }}
                </a>
            </div>
        </aside>

        {{-- La columna que desfila: pares reales de pareja. --}}
        <div class="lg:col-span-8 space-y-8">
            @if($examples->isNotEmpty())
                @foreach($examples as $i => $example)
                    <x-before-after :example="$example" :eager="$i === 0" ratio="aspect-[3/2]" />
                @endforeach
            @else
                {{-- Sin ejemplos de pareja en BD: la página degrada a los estilos,
                     nunca a un placeholder inventado. --}}
                <p class="font-serif text-pmf-secondary">{{ __('ui.lp_valentines_styles_desc') }}</p>
            @endif
        </div>
    </div>
</section>

{{-- Proceso, en banda propia. --}}
<section class="px-6 py-12 bg-champagne-dark border-y border-pmf-border">
    <div class="max-w-6xl mx-auto grid gap-8 md:grid-cols-12">
        <h2 class="md:col-span-4 font-serif text-2xl font-bold text-pmf-text">{{ __('ui.lp_valentines_how_title') }}</h2>
        <ol class="md:col-span-8 grid gap-6 sm:grid-cols-3">
            @foreach([1, 2, 3] as $n)
                <li>
                    <span class="font-serif text-xs font-bold tabular-nums text-burgundy">{{ sprintf('%02d', $n) }}</span>
                    <h3 class="font-serif text-base font-bold text-pmf-text mt-1">{{ __("ui.lp_valentines_s{$n}_title") }}</h3>
                    <p class="text-sm text-pmf-secondary leading-relaxed mt-1">{{ __("ui.lp_valentines_s{$n}_desc") }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

@if($styles->isNotEmpty())
<section class="px-6 py-12">
    <div class="max-w-6xl mx-auto">
        <h2 class="font-serif text-2xl font-bold text-pmf-text">{{ __('ui.lp_valentines_styles_title') }}</h2>
        <p class="text-sm text-pmf-secondary mt-1 max-w-lg">{{ __('ui.lp_valentines_styles_desc') }}</p>
        <ul class="mt-6 grid grid-cols-3 sm:grid-cols-6 gap-4">
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
                        <p class="mt-1.5 font-serif text-xs text-pmf-text group-hover:text-burgundy transition-colors duration-200 truncate">{{ $style->t('label') }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<section class="py-16 px-6 bg-burgundy">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-3xl font-bold text-white leading-tight">{{ __('ui.lp_valentines_cta_title') }}</h2>
        <p class="text-base text-white/80 mt-3">{{ __('ui.lp_valentines_cta_desc') }}</p>
        <a href="{{ locale_route('create.index', ['style' => 'valentines']) }}"
           class="inline-block mt-6 bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_valentines_cta') }}
        </a>
    </div>
</section>
@endsection
