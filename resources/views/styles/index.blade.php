{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Catalogue · design-system: design.md · designed-as-app
     indice filtrable de estilos --}}
@extends('layouts.app')

@section('content')
@php
    $locale = app()->getLocale();
@endphp

{{-- Hero --}}
<section class="bg-burgundy py-16 px-6">
    <div class="max-w-5xl mx-auto text-center">
        <h1 class="font-serif text-4xl lg:text-5xl font-bold text-white tracking-tight mb-4">
            {{ __('ui.styles_title') }}
        </h1>
        <p class="font-serif text-lg text-white/80 max-w-2xl mx-auto">
            {{ __('ui.styles_subtitle', ['count' => $styles->count()]) }}
        </p>
    </div>
</section>

{{-- Search & Filter --}}
@php
    // El filtrado vive en datos, no en el DOM. Antes cada tarjeta interpolaba
    // $searchText crudo dentro de un literal JS con comillas simples: un estilo
    // con apóstrofo ("L'Atelier") rompía la expresión Alpine de esa tarjeta.
    // Y el estado vacío consultaba el DOM con querySelectorAll, que no es una
    // dependencia reactiva de Alpine: se evaluaba una vez al montar, cuando aún
    // había tarjetas, así que no aparecía nunca.
    $stylesData = $styles->values()->map(fn ($s) => [
        'cat'  => $s->style_category ?? 'artistic',
        'text' => strtolower(implode(' ', (array) $s->label) . ' ' . implode(' ', (array) $s->description)),
    ]);
@endphp
<section class="py-10 px-6" x-data='{
    search: "",
    category: "all",
    styles: @json($stylesData),
    matches(i) {
        const s = this.styles[i];
        return (this.category === "all" || this.category === s.cat)
            && (this.search === "" || s.text.includes(this.search.toLowerCase()));
    },
    get visibleCount() {
        return this.styles.filter((_, i) => this.matches(i)).length;
    }
}'>
    <div class="max-w-7xl mx-auto">
        {{-- Search input --}}
        <div class="max-w-md mx-auto mb-8">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-pmf-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <label for="styles-search" class="sr-only">{{ __('ui.a11y_search_styles') }}</label>
                <input
                    type="text"
                    id="styles-search"
                    x-model="search"
                    placeholder="{{ __('ui.styles_search') }}"
                    class="w-full pl-10 pr-4 py-3 rounded-lg border border-pmf-border bg-white text-pmf-text text-sm focus:ring-2 focus:ring-burgundy focus:border-burgundy outline-none transition"
                >
            </div>
        </div>

        {{-- Category filter buttons --}}
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            @foreach([
                'all'          => __('ui.styles_all'),
                'professional' => __('ui.styles_professional'),
                'thematic'     => __('ui.styles_thematic'),
                'adventure'    => __('ui.styles_adventure'),
                'historic'     => __('ui.styles_historic'),
                'artistic'     => __('ui.styles_artistic'),
                'cultural'     => __('ui.styles_cultural'),
                'modern'       => __('ui.styles_modern'),
                'regional'     => __('ui.styles_regional'),
                'events'       => __('ui.styles_events'),
            ] as $key => $label)
                <button
                    @click="category = '{{ $key }}'"
                    :class="category === '{{ $key }}'
                        ? 'bg-burgundy text-white'
                        : 'bg-white text-pmf-text border border-pmf-border hover:bg-champagne-dark'"
                    class="px-5 py-2 rounded-full text-sm font-semibold transition"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Styles grid --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-5 max-w-5xl mx-auto">
            @foreach($styles->values() as $i => $style)
                @php
                    $cat = $style->style_category ?? 'artistic';
                    $labelLocale = $style->t('label');
                    $descLocale  = $style->t('description');
                @endphp
                <div
                    data-style-card
                    data-category="{{ $cat }}"
                    x-show="matches({{ $i }})"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden flex flex-col"
                >
                    {{-- Style image (thumbnail or placeholder) --}}
                    @php $thumbPath = "img/styles/{$style->style_key}_thumb.jpg"; $thumbWebp = "img/styles/{$style->style_key}_thumb.webp"; @endphp
                    <a href="{{ locale_route('styles.show', $style->style_key) }}" class="relative aspect-square bg-gradient-to-br from-burgundy-soft to-champagne-dark overflow-hidden block">
                        @if(file_exists(public_path($thumbPath)))
                        <picture>
                            <source srcset="{{ asset($thumbWebp) }}" type="image/webp">
                            <img src="{{ asset($thumbPath) }}" alt="{{ $labelLocale }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy">
                        </picture>
                        @else
                        <div class="w-full h-full flex items-center justify-center">
                            <span class="text-pmf-muted text-xs">{{ $labelLocale }}</span>
                        </div>
                        @endif
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <span class="bg-white/90 text-pmf-text text-xs font-semibold px-3 py-1 rounded-full">{{ __('ui.styles_detail') }}</span>
                        </div>
                    </a>

                    {{-- Card content --}}
                    <div class="p-4 flex flex-col flex-1">
                        <h3 class="font-serif text-base font-semibold text-pmf-text mb-1">{{ $labelLocale }}</h3>
                        <p class="text-xs text-pmf-muted leading-relaxed mb-4 flex-1">{{ $descLocale }}</p>
                        <a
                            href="{{ locale_route('create.index', ['style' => $style->style_key]) }}"
                            class="inline-block text-center w-full py-2.5 rounded-lg bg-burgundy text-white text-sm font-semibold hover:bg-burgundy-dark transition tracking-wide"
                        >
                            {{ __('ui.styles_try') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- No results message --}}
        <div
            x-show="visibleCount === 0"
            x-cloak
            class="text-center py-16"
        >
            <p class="font-serif text-lg text-pmf-muted">{{ __('ui.styles_no_results') }}</p>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 px-6 bg-burgundy text-center">
    <div class="max-w-2xl mx-auto space-y-6">
        <h2 class="font-serif text-3xl font-bold text-white tracking-tight">
            {{ __('ui.styles_cta_title') }}
        </h2>
        <p class="font-serif text-base text-white/80">
            {{ __('ui.styles_cta_desc') }}
        </p>
        <a href="{{ locale_route('create.index') }}" class="inline-block bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition tracking-wide">
            {{ __('ui.nav_cta') }} →
        </a>
    </div>
</section>
{{-- Lightbox modal.
     Sin role/aria-modal el lector de pantalla no sabe que se ha abierto nada, y
     sin devolver el foco al cerrar el usuario de teclado vuelve al principio de
     la página en vez de a la tarjeta que abrió. --}}
<div id="lightbox-modal" x-data="{ open: false, src: '', title: '', styleKey: '', lastFocus: null }"
     x-on:open-lightbox.window="lastFocus = document.activeElement; open = true; src = $event.detail.src; title = $event.detail.title; styleKey = $event.detail.styleKey || ''"
     x-on:keydown.escape.window="if (open) { open = false; lastFocus?.focus() }">
    <template x-if="open">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-6"
             role="dialog" aria-modal="true" :aria-label="title"
             @click.self="open = false; lastFocus?.focus()">
            <div class="relative max-w-2xl w-full">
                <button @click="open = false; lastFocus?.focus()"
                        x-init="$nextTick(() => $el.focus())"
                        aria-label="{{ __('ui.a11y_close') }}"
                        class="absolute -top-10 right-0 text-white text-3xl hover:text-gray-300 active:opacity-70 cursor-pointer">
                    <span aria-hidden="true">✕</span>
                </button>
                <img :src="src" :alt="title" class="w-full rounded-xl shadow-2xl bg-white">
                <div class="flex items-center justify-between mt-4 gap-4">
                    <p class="text-white font-serif text-lg" x-text="title"></p>
                    <a :href="'{{ locale_route('create.index') }}?style=' + styleKey"
                       class="flex-shrink-0 bg-white text-burgundy font-bold text-sm px-5 py-2.5 rounded-lg hover:bg-champagne transition">
                        {{ __('ui.styles_try') }} →
                    </a>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
