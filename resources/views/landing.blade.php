{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Bento Grid · design-system: design.md · designed-as-app
     home: hero + estilos + galeria + pasos + precios + FAQ --}}
@extends('layouts.app')

@section('content')
@include('landings._free-promo-banner')
@php $locale = app()->getLocale(); @endphp

{{-- Hero --}}
<section class="py-16 lg:py-20 px-6">
    <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-10 lg:gap-16">
        {{-- Texto — más ancho --}}
        <div class="flex-1 space-y-6 max-w-2xl">
            <span class="inline-flex items-center gap-2 bg-burgundy-soft text-burgundy text-xs font-semibold px-4 py-1.5 rounded-full tracking-wide">
                {{ __('ui.hero_badge') }}
            </span>
            <h1 class="font-serif text-4xl sm:text-5xl lg:text-[3.4rem] font-bold text-pmf-text leading-[1.1] tracking-tight">
                {{ __('ui.hero_title') }}
            </h1>
            <p class="font-serif text-lg text-pmf-secondary leading-relaxed">
                {{ __('ui.hero_subtitle') }}
            </p>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <a href="{{ locale_route('create.index') }}" class="bg-burgundy text-white font-bold text-base px-8 py-4 rounded-lg hover:bg-burgundy-dark transition tracking-wide whitespace-nowrap">
                    {{ __('ui.hero_cta') }}
                </a>
                <span class="text-xs text-pmf-muted tracking-wide">{{ __('ui.hero_no_account') }}</span>
            </div>
        </div>
        {{-- Carrusel horizontal con scroll suave + drag/touch --}}
        @php
            $heroSlides = [
                ['image' => 'img/hero/hero_family', 'label' => __('ui.hero_label_artistic')],
                ['image' => 'img/hero/hero_professional', 'label' => __('ui.hero_label_professional')],
                ['image' => 'img/hero/hero_adventure', 'label' => __('ui.hero_label_adventure')],
            ];
        @endphp
        <div class="flex-shrink-0 mx-auto lg:mx-0" style="width:380px;max-width:90vw;" x-data="{ current: 0, timer: null, init() { this.timer = setInterval(() => this.current = (this.current + 1) % 3, 5000) } }" x-init="init()">
            <div class="relative rounded-2xl shadow-lg bg-white overflow-hidden" style="aspect-ratio:1/1;">
                @foreach($heroSlides as $i => $slide)
                <div class="absolute inset-0 transition-opacity duration-700"
                     :class="current === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'">
                    <picture>
                        <source srcset="{{ asset($slide['image'] . '.webp') }}" type="image/webp">
                        <img src="{{ asset($slide['image'] . '.jpg') }}" alt="{{ $slide['label'] }}" class="w-full h-full object-cover" @if($i === 0) fetchpriority="high" @endif>
                    </picture>
                    <span class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-burgundy/85 text-white text-xs font-bold px-4 py-1.5 rounded-full tracking-wide backdrop-blur-sm whitespace-nowrap z-20">
                        {{ $slide['label'] }}
                    </span>
                </div>
                @endforeach
            </div>
            <div class="flex justify-center gap-2.5 mt-4">
                @foreach($heroSlides as $i => $slide)
                <button @click="current = {{ $i }}; clearInterval(timer); timer = setInterval(() => current = (current + 1) % 3, 5000)"
                        :class="current === {{ $i }} ? 'bg-burgundy' : 'bg-pmf-border'"
                        class="w-3 h-3 rounded-full transition-colors duration-300"></button>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Para quien / Who it's for --}}
<section class="py-12 px-6 bg-champagne-dark">
    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-3 md:grid-cols-6 gap-6 text-center">
            @foreach([
                ['icon' => '👨‍👩‍👧‍👦', 'text' => __('ui.who_families')],
                ['icon' => '👫', 'text' => __('ui.who_couples')],
                ['icon' => '💼', 'text' => __('ui.who_professional')],
                ['icon' => '💝', 'text' => __('ui.who_thematic')],
                ['icon' => '🧑‍🤝‍🧑', 'text' => __('ui.who_friends')],
                ['icon' => '🎁', 'text' => __('ui.who_gifts')],
            ] as $use)
            <div class="flex flex-col items-center gap-2">
                <span class="text-3xl">{{ $use['icon'] }}</span>
                <span class="font-serif text-sm font-semibold text-pmf-text">{{ $use['text'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Estilos / Styles — 4 aleatorios que rotan con cada cache clear --}}
<section id="styles" class="py-20 px-6">
    <div class="max-w-5xl mx-auto text-center">
        <h2 class="font-serif text-4xl font-bold text-pmf-text tracking-tight mb-3">{{ __('ui.landing_styles_title') }}</h2>
        <p class="font-serif text-pmf-secondary mb-10">{{ __('ui.landing_styles_desc', ['count' => $styles->count()]) }}</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            @foreach($styles->shuffle()->take(4) as $style)
            <a href="{{ locale_route('create.index', ['style' => $style->style_key]) }}" class="group bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden">
                @if(file_exists(public_path("img/styles/{$style->style_key}_thumb.jpg")))
                <picture>
                    <source srcset="{{ asset("img/styles/{$style->style_key}_thumb.webp") }}" type="image/webp">
                    <img src="{{ asset("img/styles/{$style->style_key}_thumb.jpg") }}" alt="{{ $style->t('label') }}" class="aspect-square w-full object-cover group-hover:scale-105 transition-transform" loading="lazy">
                </picture>
                @else
                <div class="aspect-square bg-gradient-to-br from-burgundy-soft to-champagne-dark group-hover:scale-105 transition-transform"></div>
                @endif
                <div class="p-3 text-center">
                    <h3 class="font-serif text-sm font-semibold text-pmf-text">{{ $style->t('label') }}</h3>
                    <p class="text-[11px] text-pmf-muted mt-0.5">{{ $style->t('description') }}</p>
                </div>
            </a>
            @endforeach
        </div>

        <a href="{{ locale_route('styles.index') }}" class="mt-8 inline-flex items-center gap-2 bg-burgundy text-white font-semibold text-sm px-6 py-3 rounded-lg hover:bg-burgundy-dark transition tracking-wide">
            {{ __('ui.styles_explore_all', ['count' => $styles->count()]) }}
        </a>
    </div>
</section>

{{-- Galería de ejemplos destacados --}}
@include('gallery.featured-section')

{{-- Como funciona / How it works --}}
<section id="how-it-works" class="py-20 px-6">
    <div class="max-w-5xl mx-auto text-center">
        <h2 class="font-serif text-4xl font-bold text-pmf-text tracking-tight mb-16">{{ __('ui.how_title') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            @foreach([
                ['icon' => '🎨', 'title' => __('ui.how_step1_title'), 'desc' => __('ui.how_step1_desc')],
                ['icon' => '📸', 'title' => __('ui.how_step2_title'), 'desc' => __('ui.how_step2_desc')],
                ['icon' => '✨', 'title' => __('ui.how_step3_title'), 'desc' => __('ui.how_step3_desc')],
            ] as $step)
            <div class="flex flex-col items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-burgundy-soft flex items-center justify-center text-2xl">{{ $step['icon'] }}</div>
                <h3 class="font-serif text-lg font-semibold text-pmf-text">{{ $step['title'] }}</h3>
                <p class="text-sm text-pmf-secondary leading-relaxed max-w-xs">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Testimonios / Testimonials (desde BD, rotación aleatoria) --}}
@php $testimonials = \App\Models\Testimonial::random(6); @endphp
@if($testimonials->isNotEmpty())
<section class="py-20 px-6 bg-pmf-dark">
    <div class="max-w-7xl mx-auto text-center">
        <h2 class="font-serif text-3xl font-bold text-champagne tracking-tight mb-12">{{ __('ui.testimonials_title') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($testimonials as $review)
            <div class="bg-white/5 rounded-xl p-7 text-left">
                <span class="text-[10px] font-bold text-copper uppercase tracking-wider">{{ $review->t('tag', $locale) }}</span>
                <div class="text-copper text-lg mb-2 mt-1">{!! str_repeat('★', $review->rating) !!}</div>
                <p class="font-serif text-sm text-champagne italic leading-relaxed mb-4">"{{ $review->t('quote', $locale) }}"</p>
                <p class="text-xs text-pmf-muted-dark font-semibold tracking-wide">— {{ $review->t('author', $locale) }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Precios / Pricing --}}
<section id="pricing" class="py-20 px-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-2xl shadow-md border border-pmf-border p-6 sm:p-8">
            {{-- Título dentro del contenedor --}}
            <div class="text-center mb-6">
                <h2 class="font-serif text-2xl font-bold text-pmf-text tracking-tight mb-1">{{ __('ui.pricing_title') }}</h2>
                @if(config('genpic.free_web_basic_enabled'))
                    <p class="text-sm text-green-700 font-semibold mt-1">{!! __('ui.free_promo_price_note') !!}</p>
                @else
                    <p class="text-xs text-pmf-muted">{{ __('ui.pricing_subtitle') }}</p>
                @endif
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl p-5 text-center {{ config('genpic.free_web_basic_enabled') ? 'bg-green-50 border-2 border-green-500' : 'bg-champagne' }}">
                    @if(config('genpic.free_web_basic_enabled'))
                        <p class="font-serif text-sm text-pmf-muted line-through">{{ __('ui.pricing_price_web') }}</p>
                        <p class="font-serif text-2xl font-bold text-green-600">{{ __('ui.free_price') }}</p>
                    @else
                        <p class="font-serif text-3xl font-bold text-burgundy">{{ __('ui.pricing_price_web') }}</p>
                    @endif
                    <p class="text-xs text-pmf-secondary mt-2 leading-snug">{{ __('ui.pricing_web') }}</p>
                    @if(config('genpic.free_web_basic_enabled'))
                        <span class="inline-block bg-green-600 text-white text-[8px] font-bold px-3 py-0.5 rounded-full mt-2">{{ __('ui.free_limited_badge') }}</span>
                    @endif
                </div>
                <div class="rounded-xl p-5 bg-champagne text-center">
                    <p class="font-serif text-3xl font-bold text-burgundy">{{ __('ui.pricing_price_web_hd') }}</p>
                    <p class="text-xs text-pmf-secondary mt-2 leading-snug">{{ __('ui.pricing_web_hd') }}</p>
                </div>
                <div class="rounded-xl p-5 bg-burgundy-soft border-2 border-burgundy text-center">
                    <p class="font-serif text-3xl font-bold text-burgundy">{{ __('ui.pricing_price_wallpapers') }}</p>
                    <p class="text-xs text-pmf-secondary mt-2 leading-snug">{{ __('ui.pricing_wallpapers') }}</p>
                    <span class="inline-block bg-burgundy text-white text-[8px] font-bold px-3 py-0.5 rounded-full mt-2">{{ __('ui.pricing_popular') }}</span>
                </div>
                <div class="rounded-xl p-5 bg-champagne text-center">
                    <p class="font-serif text-3xl font-bold text-burgundy">{{ __('ui.pricing_price_ultra') }}</p>
                    <p class="text-xs text-pmf-secondary mt-2 leading-snug">{{ __('ui.pricing_ultra') }}</p>
                </div>
            </div>

            {{-- Badges de confianza --}}
            <div class="flex flex-wrap justify-center gap-4 mt-8 text-[11px] text-pmf-muted">
                <span>🔒 {{ __('ui.pricing_badge_secure') }}</span>
                <span>💳 {{ __('ui.pricing_badge_methods') }}</span>
                <span>📥 {{ __('ui.pricing_badge_instant') }}</span>
                <span>✨ {{ __('ui.pricing_badge_no_watermark') }}</span>
            </div>

            @if($locale === 'en')
            <p class="text-[10px] text-pmf-muted text-center mt-3">{{ __('ui.pricing_approx') }}</p>
            @endif
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="py-20 px-6">
    <div class="max-w-3xl mx-auto">
        <h2 class="font-serif text-4xl font-bold text-pmf-text tracking-tight text-center mb-12">{{ __('ui.faq_title') }}</h2>
        <div class="divide-y divide-pmf-border">
            @foreach([
                ['q' => __('ui.faq_1_q'), 'a' => __('ui.faq_1_a')],
                ['q' => __('ui.faq_2_q'), 'a' => __('ui.faq_2_a')],
                ['q' => __('ui.faq_3_q'), 'a' => __('ui.faq_3_a')],
                ['q' => __('ui.faq_4_q'), 'a' => __('ui.faq_4_a')],
                ['q' => __('ui.faq_5_q'), 'a' => __('ui.faq_5_a')],
                ['q' => __('ui.faq_6_q'), 'a' => __('ui.faq_6_a')],
            ] as $faq)
            <details class="group py-5">
                <summary class="flex justify-between items-center cursor-pointer list-none font-serif text-lg font-semibold text-pmf-text">
                    {{ $faq['q'] }}
                    <span class="text-pmf-muted group-open:rotate-45 transition-transform text-xl">+</span>
                </summary>
                <p class="mt-3 font-serif text-sm text-pmf-secondary leading-relaxed">{{ $faq['a'] }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA Final --}}
<section class="py-20 px-6 bg-burgundy text-center">
    <div class="max-w-2xl mx-auto space-y-7">
        <h2 class="font-serif text-4xl font-bold text-white tracking-tight">{{ __('ui.cta_title') }}</h2>
        <p class="font-serif text-lg text-white/80">{{ __('ui.cta_subtitle') }}</p>
        <a href="{{ locale_route('create.index') }}" class="inline-block bg-white text-burgundy font-bold text-base px-8 py-4 rounded-lg hover:bg-champagne transition tracking-wide">
            {{ __('ui.cta_btn') }}
        </a>
        <p class="text-xs text-white/60 tracking-wide">{{ __('ui.cta_trust') }}</p>
    </div>
</section>

@push('jsonld')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        @foreach([
            ['q' => __('ui.faq_1_q'), 'a' => __('ui.faq_1_a')],
            ['q' => __('ui.faq_2_q'), 'a' => __('ui.faq_2_a')],
            ['q' => __('ui.faq_3_q'), 'a' => __('ui.faq_3_a')],
            ['q' => __('ui.faq_4_q'), 'a' => __('ui.faq_4_a')],
            ['q' => __('ui.faq_5_q'), 'a' => __('ui.faq_5_a')],
            ['q' => __('ui.faq_6_q'), 'a' => __('ui.faq_6_a')]
        ] as $i => $faq)
        {
            "@type": "Question",
            "name": @json($faq['q']),
            "acceptedAnswer": {
                "@type": "Answer",
                "text": @json($faq['a'])
            }
        }@if($i < 5),@endif
        @endforeach
    ]
}
</script>
@endpush
@endsection
