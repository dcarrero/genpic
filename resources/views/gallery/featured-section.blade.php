{{-- Hallmark · genre: editorial · family: marketing · macrostructure: Portfolio Grid · design-system: design.md · designed-as-app
     parcial embebido en la home --}}
{{-- Sección galería destacada para incluir en la landing --}}
@php
    $featured = \App\Models\GalleryExample::featured()->with('artisticStyle')->inRandomOrder()->take(6)->get();
@endphp

@if($featured->isNotEmpty())
<section id="gallery" class="py-16 bg-champagne-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="font-serif text-3xl font-bold text-pmf-text mb-3">{{ __('ui.gallery_section_title') }}</h2>
            <p class="font-serif text-pmf-secondary">{{ __('ui.gallery_section_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($featured as $example)
            @php
                $locale = app()->getLocale();
                $styleName = $example->artisticStyle->t('label');
            @endphp
            <a href="{{ locale_route('gallery.show', $example->id) }}" class="bg-white rounded-xl overflow-hidden shadow-sm border border-pmf-border hover:shadow-md transition group block">
                <div class="relative overflow-hidden" style="height: 240px;">
                    <picture>
                        <source srcset="{{ asset('img/gallery/result_' . $example->id . '.webp') }}" type="image/webp">
                        <img
                            src="{{ asset('img/gallery/result_' . $example->id . '.jpg') }}"
                            alt="{{ $styleName }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                            loading="lazy"
                        >
                    </picture>
                    <span class="absolute top-2 left-2 z-10 bg-burgundy/80 text-white text-[9px] font-bold px-2 py-0.5 rounded">
                        🎨 {{ $styleName }}
                    </span>
                </div>
                <div class="p-3 flex items-center justify-between">
                    <span class="font-serif text-xs font-semibold text-pmf-text">{{ $styleName }}</span>
                    <span class="text-[10px] text-burgundy font-semibold group-hover:underline">
                        {{ __('ui.gallery_try_style') }} →
                    </span>
                </div>
            </a>
            @endforeach
        </div>

        <div class="text-center mt-8">
            <a href="{{ locale_route('gallery.index') }}" class="text-burgundy font-semibold text-sm hover:underline">
                {{ __('ui.gallery_see_all') }} →
            </a>
        </div>
    </div>
</section>
@endif
