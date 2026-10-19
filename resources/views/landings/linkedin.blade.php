{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Conversational FAQ · design-system: design.md · designed-as-app
     Producto que se enfrenta a escepticismo: se responde, no se proclama.
     Stat-Led descartada — las cifras de esta página no tienen fuente (design.md).
     El azul #0A66C2 (marca de LinkedIn) retirado: no es nuestro acento y sugería afiliación. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

@php $heroPair = $examples->first(); @endphp

<section class="px-6 pt-14 pb-10 border-b border-pmf-border">
    <div class="max-w-3xl mx-auto">
        <p class="font-serif text-sm text-burgundy mb-2">{{ __('ui.lp_linkedin_badge') }}</p>
        <h1 class="font-serif text-3xl sm:text-4xl font-bold text-pmf-text leading-tight tracking-tight max-w-2xl">
            {{ __('ui.lp_linkedin_h1') }}
        </h1>
        <p class="text-lg text-pmf-secondary leading-relaxed mt-4 max-w-xl">
            {{ __('ui.lp_linkedin_subtitle') }}
        </p>
        <a href="{{ locale_route('create.index', ['style' => 'linkedin_photo']) }}"
           class="inline-block mt-6 border-2 border-burgundy text-burgundy font-bold text-sm px-7 py-3 rounded-lg hover:bg-burgundy hover:text-white transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_linkedin_cta') }}
        </a>
        <p class="text-xs text-pmf-muted mt-3">{{ __('ui.hero_no_account') }}</p>
    </div>
</section>

{{-- La prueba antes que el argumento. --}}
@if($heroPair)
<section class="px-6 py-10">
    <div class="max-w-3xl mx-auto">
        <x-before-after :example="$heroPair" :eager="true" ratio="aspect-[3/2]" />
    </div>
</section>
@endif

{{-- El cuerpo: preguntas, respuestas cortas. <details> nativo: cero JS, teclado gratis. --}}
<section class="px-6 pb-14">
    <div class="max-w-3xl mx-auto">
        <h2 class="font-serif text-2xl font-bold text-pmf-text mb-6">{{ __('ui.lp_linkedin_why_title') }}</h2>

        @php
            $qa = [
                ['q' => __('ui.lp_linkedin_w1_title'), 'a' => __('ui.lp_linkedin_w1_desc')],
                ['q' => __('ui.lp_linkedin_w2_title'), 'a' => __('ui.lp_linkedin_w2_desc')],
                ['q' => __('ui.lp_linkedin_w3_title'), 'a' => __('ui.lp_linkedin_w3_desc')],
                ['q' => __('ui.lp_linkedin_s1_title'), 'a' => __('ui.lp_linkedin_s1_desc')],
                ['q' => __('ui.lp_linkedin_s2_title'), 'a' => __('ui.lp_linkedin_s2_desc')],
                ['q' => __('ui.lp_linkedin_s3_title'), 'a' => __('ui.lp_linkedin_s3_desc')],
            ];
        @endphp

        <div class="divide-y divide-pmf-border border-y border-pmf-border">
            @foreach($qa as $item)
                <details class="group py-4">
                    <summary class="flex justify-between items-start gap-4 cursor-pointer list-none">
                        <h3 class="font-serif text-base sm:text-lg font-semibold text-pmf-text">{{ $item['q'] }}</h3>
                        <span aria-hidden="true"
                              class="shrink-0 mt-1 text-burgundy text-lg leading-none group-open:rotate-45 transition-transform duration-200">+</span>
                    </summary>
                    <p class="text-sm text-pmf-secondary leading-relaxed mt-3 max-w-2xl">{{ $item['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- Cifras: se mantienen por decisión del propietario (design.md § Deuda
     conocida), pero a tamaño de nota — no como titular. --}}
<section class="px-6 pb-14">
    <div class="max-w-3xl mx-auto">
        <ul class="flex flex-wrap gap-x-8 gap-y-2 text-xs text-pmf-muted">
            @foreach([1, 2, 3] as $n)
                <li>
                    <span class="font-semibold text-pmf-secondary tabular-nums">{{ __("ui.lp_linkedin_stat{$n}_num") }}</span>
                    {{ __("ui.lp_linkedin_stat{$n}_label") }}
                </li>
            @endforeach
        </ul>
    </div>
</section>

@if($examples->count() > 1)
<section class="px-6 pb-14">
    <div class="max-w-3xl mx-auto grid gap-6 sm:grid-cols-3">
        @foreach($examples->slice(1) as $example)
            <x-before-after :example="$example" ratio="aspect-square" />
        @endforeach
    </div>
</section>
@endif

<section class="py-16 px-6 bg-burgundy">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-3xl font-bold text-white leading-tight">{{ __('ui.lp_linkedin_cta_title') }}</h2>
        <p class="text-base text-white/80 mt-3">{{ __('ui.lp_linkedin_cta_desc') }}</p>
        <a href="{{ locale_route('create.index', ['style' => 'linkedin_photo']) }}"
           class="inline-block mt-6 bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_linkedin_cta') }}
        </a>
    </div>
</section>
@endsection
