{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Narrative Workflow · design-system: design.md · designed-as-app
     Proceso genuinamente ordinal: etapas numeradas, filete grueso entre ellas.
     Los 3 pasos ya existían como contenido — aquí son la forma de la página. --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')

{{-- Apertura: el trámite se anuncia como trámite. --}}
<section class="px-6 pt-14 pb-12 bg-champagne-dark border-b-4 border-pmf-dark">
    <div class="max-w-5xl mx-auto">
        <p class="font-serif text-sm text-burgundy mb-2">{{ __('ui.lp_cv_badge') }}</p>
        <h1 class="font-serif text-4xl sm:text-5xl font-bold text-pmf-text leading-[1.1] tracking-tight max-w-2xl">
            {{ __('ui.lp_cv_h1') }}
        </h1>
        <p class="text-lg text-pmf-secondary leading-relaxed mt-4 max-w-xl">
            {{ __('ui.lp_cv_subtitle') }}
        </p>
    </div>
</section>

{{-- Las etapas. El número es el encabezado; el filete grueso, el divisor. --}}
@php $pairs = $examples->values(); @endphp

@foreach([1, 2, 3] as $i => $n)
    <section class="px-6 py-12 {{ $n < 3 ? 'border-b-4 border-pmf-dark' : '' }} {{ $n % 2 === 0 ? 'bg-champagne-dark' : '' }}">
        <div class="max-w-5xl mx-auto grid gap-8 md:grid-cols-12 md:items-start">
            <div class="md:col-span-3">
                <p class="font-serif text-5xl font-bold text-burgundy tabular-nums leading-none">
                    {{ $n }}.0
                </p>
            </div>
            <div class="md:col-span-5">
                <h2 class="font-serif text-xl font-bold text-pmf-text">{{ __("ui.lp_cv_s{$n}_title") }}</h2>
                <p class="text-sm text-pmf-secondary leading-relaxed mt-2">{{ __("ui.lp_cv_s{$n}_desc") }}</p>
            </div>
            <div class="md:col-span-4">
                @if($pairs->get($i))
                    <x-before-after :example="$pairs->get($i)" :eager="$i === 0" ratio="aspect-[3/2]" />
                @endif
            </div>
        </div>
    </section>
@endforeach

{{-- Lo que ganas, en lista seca. Sin tarjetas, sin iconos. --}}
<section class="px-6 py-14 border-t-4 border-pmf-dark">
    <div class="max-w-5xl mx-auto grid gap-8 md:grid-cols-12">
        <h2 class="md:col-span-4 font-serif text-2xl font-bold text-pmf-text">{{ __('ui.lp_cv_benefits_title') }}</h2>
        <dl class="md:col-span-8 divide-y divide-pmf-border border-y border-pmf-border">
            @foreach([1, 2, 3] as $n)
                <div class="py-4">
                    <dt class="font-serif text-base font-bold text-pmf-text">{{ __("ui.lp_cv_b{$n}_title") }}</dt>
                    <dd class="text-sm text-pmf-secondary leading-relaxed mt-1">{{ __("ui.lp_cv_b{$n}_desc") }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

{{-- "Empieza por la etapa 1" — el CTA global de esta forma. --}}
<section class="py-16 px-6 bg-burgundy">
    <div class="max-w-2xl mx-auto">
        <h2 class="font-serif text-3xl font-bold text-white leading-tight">{{ __('ui.lp_cv_cta_title') }}</h2>
        <p class="text-base text-white/80 mt-3">{{ __('ui.lp_cv_cta_desc') }}</p>
        <a href="{{ locale_route('create.index', ['style' => 'cv_photo']) }}"
           class="inline-block mt-6 bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition-colors duration-200 tracking-wide whitespace-nowrap">
            {{ __('ui.lp_cv_cta') }}
        </a>
    </div>
</section>
@endsection
