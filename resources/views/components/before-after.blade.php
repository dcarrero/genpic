{{-- Hallmark · component: before-after · genre: editorial · design-system: design.md
     Diptico antes/despues. Sin slider: el del proyecto cubre la foto con un
     input[type=range] de opacity 0 y compite con el scroll tactil.
     surface="dark" obligatorio sobre bg-pmf-dark (pmf-muted falla AA ahi). --}}
@props([
    'example',
    'eager' => false,
    'ratio' => 'aspect-[4/5]',
    // pmf-muted y pmf-muted-dark no son intercambiables: cada uno falla AA sobre
    // el fondo del otro (design.md § Paleta). El componente no puede adivinar
    // dónde lo montan, así que la superficie se declara.
    'surface' => 'light', // light | dark
])

@php
    $locale  = app()->getLocale();
    $style   = $example->artisticStyle?->t('label') ?? '';
    // Las vistas del proyecto sirven la galería por id desde public/img/gallery/,
    // no por original_path/result_path (que son rutas de Wasabi).
    $orig    = 'img/gallery/original_' . $example->id;
    $result  = 'img/gallery/result_' . $example->id;
    $loading = $eager ? 'eager' : 'lazy';
@endphp

{{-- Díptico honesto: las dos imágenes visibles a la vez, sin slider.
     El slider del proyecto cubre la foto entera con un input range de opacidad 0
     y secuestra el scroll táctil. Aquí no hay nada que arrastrar. --}}
<figure {{ $attributes->merge(['class' => 'group']) }}>
    <div class="grid grid-cols-2 gap-px bg-pmf-border overflow-hidden rounded-lg">
        <div class="relative {{ $ratio }} bg-champagne-dark">
            <picture>
                <source srcset="{{ asset($orig . '.webp') }}" type="image/webp">
                <img
                    src="{{ asset($orig . '.jpg') }}"
                    alt="{{ __('ui.ba_alt_before') }}"
                    class="absolute inset-0 w-full h-full object-cover"
                    loading="{{ $loading }}"
                    @if($eager) fetchpriority="high" @endif
                    decoding="async"
                >
            </picture>
            <figcaption class="absolute bottom-0 left-0 max-w-full bg-pmf-dark/85 text-champagne text-[10px] font-semibold tracking-wide uppercase px-1.5 py-0.5 truncate">
                {{ __('ui.ba_before') }}
            </figcaption>
        </div>
        <div class="relative {{ $ratio }} bg-champagne-dark">
            <picture>
                <source srcset="{{ asset($result . '.webp') }}" type="image/webp">
                <img
                    src="{{ asset($result . '.jpg') }}"
                    alt="{{ $style ? __('ui.ba_alt_after', ['style' => $style]) : __('ui.ba_after') }}"
                    class="absolute inset-0 w-full h-full object-cover"
                    loading="{{ $loading }}"
                    @if($eager) fetchpriority="high" @endif
                    decoding="async"
                >
            </picture>
            <figcaption class="absolute bottom-0 left-0 max-w-full bg-burgundy/90 text-white text-[10px] font-semibold tracking-wide uppercase px-1.5 py-0.5 truncate">
                {{ __('ui.ba_after') }}
            </figcaption>
        </div>
    </div>
    @if($style)
        <p @class([
            'mt-2 font-serif text-xs',
            'text-pmf-muted' => $surface === 'light',       // 5.20:1 sobre champagne
            'text-pmf-muted-dark' => $surface === 'dark',   // 5.33:1 sobre pmf-dark
        ])>{{ $style }}</p>
    @endif
</figure>
