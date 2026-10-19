{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Portfolio Grid · design-system: design.md · designed-as-app
     rejilla de trabajos reales --}}
@extends('layouts.app')

@php $currentType = request('type'); @endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="text-center mb-12">
        <h1 class="font-serif text-4xl font-bold text-pmf-text mb-4">{{ __('ui.gallery_title') }}</h1>
        <p class="font-serif text-lg text-pmf-secondary max-w-2xl mx-auto">{{ __('ui.gallery_subtitle') }}</p>
    </div>

    {{-- Filtros por tipo (links, no JS) --}}
    <div class="flex flex-wrap justify-center gap-3 mb-10">
        <a
            href="{{ locale_route('gallery.index') }}"
            class="px-5 py-2 rounded-full text-sm font-semibold transition {{ !$currentType ? 'bg-burgundy text-white' : 'bg-white text-pmf-text border border-pmf-border hover:border-burgundy' }}"
        >
            {{ __('ui.gallery_filter_all') }}
        </a>
        @foreach($photoTypes as $typeKey => $labels)
        <a
            href="{{ locale_route('gallery.index', ['type' => $typeKey]) }}"
            class="px-5 py-2 rounded-full text-sm font-semibold transition {{ $currentType === $typeKey ? 'bg-burgundy text-white' : 'bg-white text-pmf-text border border-pmf-border hover:border-burgundy' }}"
        >
            {{ $labels[app()->getLocale()] ?? $labels['es'] }}
        </a>
        @endforeach
    </div>

    {{-- Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($examples as $example)
        @php
            $locale = app()->getLocale();
            $styleName = $example->artisticStyle->t('label');
        @endphp
        <a
            href="{{ locale_route('gallery.show', $example->id) }}"
            class="bg-white rounded-2xl overflow-hidden shadow-sm border border-pmf-border hover:shadow-md transition group block"
        >
            <div class="relative overflow-hidden" style="height: 280px;">
                <picture>
                    <source srcset="{{ asset('img/gallery/result_' . $example->id . '.webp') }}" type="image/webp">
                    <img
                        src="{{ asset('img/gallery/result_' . $example->id . '.jpg') }}"
                        alt="{{ $styleName }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                        loading="lazy"
                    >
                </picture>
                <span class="absolute top-2 left-2 bg-burgundy/80 text-white text-[9px] font-bold px-2 py-0.5 rounded">
                    🎨 {{ $styleName }}
                </span>
                <div class="absolute bottom-2 right-2 bg-white/80 rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition">
                    <svg class="w-4 h-4 text-pmf-text" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/></svg>
                </div>
            </div>

            <div class="p-4">
                <h3 class="font-serif text-sm font-semibold text-pmf-text">{{ $styleName }}</h3>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-[10px] text-pmf-muted bg-pmf-border/50 px-2 py-0.5 rounded-full">
                        {{ $photoTypes[$example->photo_type][$locale] ?? $example->photo_type }}
                    </span>
                    <span class="text-xs font-semibold text-burgundy group-hover:underline">
                        {{ __('ui.gallery_try_style') }} →
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Paginación simple --}}
    @if($examples->hasPages())
    <div class="mt-14 mb-4 flex items-center justify-center gap-4">
        @if($examples->onFirstPage())
            <span class="px-5 py-2.5 rounded-lg bg-pmf-border/50 text-pmf-muted text-sm font-semibold cursor-not-allowed">
                ← {{ __('ui.gallery_prev') }}
            </span>
        @else
            <a href="{{ $examples->previousPageUrl() }}" class="px-5 py-2.5 rounded-lg bg-white border border-pmf-border text-pmf-text text-sm font-semibold hover:border-burgundy transition">
                ← {{ __('ui.gallery_prev') }}
            </a>
        @endif

        <span class="text-xs text-pmf-muted">
            {{ $examples->currentPage() }} / {{ $examples->lastPage() }}
        </span>

        @if($examples->hasMorePages())
            <a href="{{ $examples->nextPageUrl() }}" class="px-5 py-2.5 rounded-lg bg-burgundy text-white text-sm font-semibold hover:bg-burgundy-dark transition">
                {{ __('ui.gallery_next') }} →
            </a>
        @else
            <span class="px-5 py-2.5 rounded-lg bg-pmf-border/50 text-pmf-muted text-sm font-semibold cursor-not-allowed">
                {{ __('ui.gallery_next') }} →
            </span>
        @endif
    </div>
    @endif

</div>
@endsection
