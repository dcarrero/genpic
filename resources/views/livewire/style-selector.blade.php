{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     seleccion de estilo --}}
<div class="w-full max-w-2xl mx-auto">
    {{-- Estilo seleccionado + botón cambiar --}}
    @if($selected && !$showDropdown)
    <div class="flex items-center justify-center gap-3">
        <div class="flex items-center gap-3 bg-white rounded-xl px-4 py-3 shadow-sm border border-pmf-border">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-burgundy-soft to-champagne-dark flex-shrink-0"></div>
            <div class="text-left">
                <p class="font-serif text-sm font-semibold text-pmf-text">
                    {{ $selected->t('label') }}
                </p>
                <p class="text-[11px] text-pmf-muted">
                    {{ $selected->t('description') }}
                </p>
            </div>
        </div>
        <button wire:click="toggleDropdown" aria-expanded="false" class="text-sm text-burgundy font-semibold hover:underline active:opacity-70 whitespace-nowrap">
            {{ __('ui.change_style') }}
        </button>
    </div>
    @endif

    {{-- Buscador desplegable --}}
    @if($showDropdown || !$selected)
    <div class="bg-white rounded-2xl shadow-md border border-pmf-border p-4 space-y-3">
        <div class="flex items-center justify-between">
            <p class="font-serif text-sm font-semibold text-pmf-text">
                {{ __('ui.style_choose') }}
            </p>
            @if($selected)
            <button wire:click="toggleDropdown" aria-label="{{ __('ui.a11y_close') }}"
                    class="text-xs text-pmf-muted hover:text-burgundy active:opacity-70">
                <span aria-hidden="true">✕</span>
            </button>
            @endif
        </div>

        {{-- Input de búsqueda --}}
        <div class="relative">
            <label for="ss-search" class="sr-only">{{ __('ui.a11y_search_styles') }}</label>
            <input
                type="text"
                id="ss-search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('ui.styles_search') }}"
                class="w-full px-4 py-2.5 pl-9 rounded-lg bg-champagne border border-pmf-border text-sm text-pmf-text focus:ring-burgundy focus:border-burgundy"
            >
            <svg class="absolute left-3 top-3 w-4 h-4 text-pmf-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </div>

        {{-- Grid de resultados (compacto) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-64 overflow-y-auto">
            @forelse($styles as $style)
            <button
                wire:click="selectStyle('{{ $style->style_key }}')"
                class="flex items-center gap-2 p-2 rounded-lg text-left transition hover:bg-burgundy-soft
                    {{ $selectedStyle === $style->style_key ? 'bg-burgundy-soft ring-1 ring-burgundy' : 'bg-champagne' }}"
            >
                <div class="w-8 h-8 rounded bg-gradient-to-br from-burgundy-soft to-champagne-dark flex-shrink-0"></div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-pmf-text truncate">
                        {{ $style->t('label') }}
                    </p>
                    <p class="text-[10px] text-pmf-muted truncate">
                        {{ $style->t('description') }}
                    </p>
                </div>
            </button>
            @empty
            <p class="col-span-full text-center text-sm text-pmf-muted py-4">{{ __('ui.styles_no_results') }}</p>
            @endforelse
        </div>

        {{-- Link a galería completa --}}
        <div class="text-center pt-1">
            <a href="{{ locale_route('styles.index') }}" class="text-xs text-burgundy font-semibold hover:underline">
                {{ __('ui.styles_see_all_detail') }}
            </a>
        </div>
    </div>
    @endif
</div>
