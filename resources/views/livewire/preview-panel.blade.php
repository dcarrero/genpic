{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     paso 3: preview + compra --}}
{{-- aria-live mientras se genera: wire:poll pasa de "generando" a "listo" sin
     recargar, y sin esto el lector de pantalla no anuncia que el retrato ya está.
     Solo durante la espera — una región viva permanente sería ruido. --}}
<div class="flex flex-col lg:flex-row gap-12"
     @if(!$isReady && !$hasFailed) wire:poll.3s="checkReady" aria-live="polite" @endif>
    {{-- Izquierda: Preview --}}
    <div class="flex-1 flex flex-col items-center gap-4">
        @if($hasFailed)
            {{-- Error: generación fallida --}}
            <div class="relative w-full max-w-lg bg-red-50 rounded-xl shadow-lg overflow-hidden border border-red-200">
                <div class="w-full py-16 flex flex-col items-center justify-center gap-5 px-8">
                    <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <p class="font-serif text-lg font-semibold text-red-700 text-center">
                        {{ __('ui.generation_failed_title', ['default' => 'La generación ha fallado']) }}
                    </p>
                    <p class="font-serif text-sm text-red-600 text-center leading-relaxed">
                        {{ __('ui.generation_failed_message', ['default' => 'Ha ocurrido un error al generar tu retrato. Por favor, inténtalo de nuevo.']) }}
                    </p>
                    <a href="{{ locale_route('create.index') }}"
                       class="inline-flex items-center gap-2 bg-burgundy text-white font-bold px-6 py-3 rounded-lg hover:bg-burgundy-dark transition text-sm tracking-wide">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182" />
                        </svg>
                        {{ __('ui.generation_failed_retry', ['default' => 'Intentar de nuevo']) }}
                    </a>
                </div>
            </div>
        @elseif($isReady && $watermarkedUrl)
            {{-- Retrato listo — slider before/after --}}
            <span class="inline-flex items-center gap-2 bg-burgundy-soft text-burgundy text-xs font-semibold px-4 py-1.5 rounded-full tracking-wide">
                👁 {{ __('ui.preview_slide') }}
            </span>

            <style>
                .preview-slider { position: relative; overflow: hidden; border-radius: 0.75rem; }
                .preview-slider img { display: block; width: 100%; height: auto; }
                .preview-slider .ps-overlay { position: absolute; inset: 0; overflow: hidden; }
                .preview-slider .ps-overlay img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
                .preview-slider .ps-line { position: absolute; top: 0; bottom: 0; width: 3px; background: white; z-index: 20; box-shadow: 0 0 10px rgba(0,0,0,0.5); pointer-events: none; }
                .preview-slider .ps-handle { position: absolute; top: 50%; z-index: 30; width: 40px; height: 40px; background: white; border-radius: 50%; box-shadow: 0 2px 10px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; pointer-events: none; }
                .preview-slider:has(input[type=range]:focus-visible) .ps-handle { outline: 3px solid #7A2D3B; outline-offset: 2px; }
                .preview-slider input[type=range] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: col-resize; z-index: 40; margin: 0;
                    /* touch-action: el range cubre toda la imagen; sin esto se traga el
                       gesto vertical y el usuario queda atrapado sin poder pasar del retrato.
                       pan-y deja el scroll al navegador y solo el arrastre horizontal llega aqui. */
                    touch-action: pan-y; }
                .preview-slider .ps-label { position: absolute; top: 8px; z-index: 10; font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.08em; color: white; }
            </style>

            <div class="preview-slider w-full max-w-lg shadow-lg border border-pmf-border" id="ps-container" wire:ignore>
                {{-- Original (base) --}}
                <img src="{{ $originalUrl }}" alt="Original">

                {{-- Estilo watermarked (overlay, empieza en 25% = muestra 75% del estilo) --}}
                <div class="ps-overlay" id="ps-overlay" style="clip-path: inset(0 0 0 25%)">
                    <img src="{{ $watermarkedUrl }}" alt="{{ __('ui.preview_your_portrait') }}">
                </div>

                <div class="ps-line" id="ps-line" style="left: calc(25% - 1.5px)"></div>
                <div class="ps-handle" id="ps-handle" style="left: 25%; transform: translate(-50%, -50%)">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#7A2D3B" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l-3 3 3 3m8-6l3 3-3 3"/></svg>
                </div>

                <div class="ps-label left-2" style="background:rgba(0,0,0,0.6);">📸 Original</div>
                <div class="ps-label right-2" style="background:#7A2D3B;">🎨 {{ __('ui.preview_your_portrait') }}</div>

                <label for="ps-range" class="sr-only">{{ __('ui.a11y_compare_slider') }}</label>
                <input type="range" min="0" max="100" value="25" id="ps-range"
                       aria-label="{{ __('ui.a11y_compare_slider') }}">
            </div>

            <script>
                document.getElementById('ps-range').addEventListener('input', function() {
                    var v = this.value;
                    document.getElementById('ps-overlay').style.clipPath = 'inset(0 0 0 ' + v + '%)';
                    document.getElementById('ps-line').style.left = 'calc(' + v + '% - 1.5px)';
                    document.getElementById('ps-handle').style.left = v + '%';
                });
            </script>

            <div class="text-center space-y-1 max-w-md">
                <p class="font-serif text-sm text-pmf-secondary">
                    {{ __('ui.preview_watermark_note') }}
                </p>
                @if($session->ab_variant === 'square')
                <p class="text-xs text-pmf-muted">
                    {{ __('ui.preview_crop_note') }}
                    <button wire:click="resetOrientation" class="text-burgundy font-semibold hover:underline">
                        {{ __('ui.preview_show_original_proportions') }}
                    </button>
                </p>
                @endif
                <p class="text-xs text-pmf-muted">
                    {{ __('ui.preview_not_satisfied') }}
                    <a href="{{ locale_route('create.index') }}" class="text-burgundy font-semibold hover:underline">
                        {{ __('ui.preview_try_another') }}
                    </a>.
                </p>
            </div>
        @else
            {{-- Recuperando / generando --}}
            <span class="inline-flex items-center gap-2 bg-burgundy-soft text-burgundy text-xs font-semibold px-4 py-1.5 rounded-full tracking-wide">
                🔐 {{ __('ui.preview_verified') }}
            </span>

            <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg overflow-hidden border border-pmf-border">
                <div class="w-full py-24 flex flex-col items-center justify-center gap-5 bg-champagne-dark px-8">
                    <div class="relative">
                        <svg class="w-12 h-12 text-burgundy animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40 60" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="font-serif text-lg font-semibold text-pmf-text text-center">
                        {{ __('ui.preview_retrieving') }}
                    </p>
                    <p class="font-serif text-sm text-pmf-secondary text-center leading-relaxed">
                        {{ __('ui.preview_processing_note') }}
                    </p>
                    <span class="bg-burgundy text-white text-xs font-bold px-4 py-1.5 rounded-full">
                        {{ __('ui.processing_time') }}
                    </span>

                    {{-- Indeterminada: sin porcentaje real que reportar (antes: 65% fijo) --}}
                    <div class="w-64 max-w-full h-1.5 bg-pmf-border rounded-full overflow-hidden progress-indeterminate"
                         role="progressbar" aria-valuetext="{{ __('ui.preview_processing_note') }}">
                        <div class="h-full w-2/5 bg-burgundy rounded-full progress-indeterminate__bar"></div>
                    </div>
                </div>
            </div>

            <p class="font-serif text-sm text-pmf-secondary text-center">
                {{ __('ui.preview_seconds_note') }}
            </p>
        @endif
    </div>

    {{-- Derecha: Productos o Upsell Selector --}}
    <div class="w-full lg:w-[420px] space-y-5">

        @if(!$showUpsell)
            {{-- PRODUCTOS NORMALES --}}
            <h2 class="font-serif text-2xl font-bold text-pmf-text">{{ __('ui.preview_choose') }}</h2>

            @foreach($this->products as $product)
            @php
                $locale = app()->getLocale();
                $name = $product->t('name');
                $desc = $product->t('description');
                $price = $product->formattedPrice();
                $isFreeWebBasic = $freePromoEnabled && $product->product_key === 'web_basic';
            @endphp
            <div class="bg-white rounded-xl p-5 transition {{ $product->highlight ? 'border-2 border-burgundy shadow-md' : ($isFreeWebBasic ? 'border-2 border-green-500 shadow-md' : 'border border-pmf-border hover:shadow-sm') }}">
                @if($isFreeWebBasic)
                    <span class="inline-block bg-green-600 text-white text-[10px] font-bold px-3 py-0.5 rounded-full tracking-wider mb-2">{{ __('ui.free_limited_badge') }}</span>
                @elseif($product->highlight)
                    <span class="inline-block bg-burgundy text-white text-[10px] font-bold px-3 py-0.5 rounded-full tracking-wider mb-2">{{ __('ui.most_popular') }}</span>
                @endif
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-serif text-base font-semibold text-pmf-text">{{ $name }}</h3>
                        <p class="text-xs text-pmf-muted">{{ $desc }}</p>
                    </div>
                    @if($isFreeWebBasic)
                        <div class="text-right">
                            <p class="font-serif text-sm text-pmf-muted line-through">{{ $price }}</p>
                            <p class="font-serif text-xl font-bold text-green-600">{{ __('ui.free_price') }}</p>
                        </div>
                    @else
                        <p class="font-serif text-xl font-bold {{ $product->highlight ? 'text-burgundy' : 'text-pmf-text' }}">{{ $price }}</p>
                    @endif
                </div>
                @if($isReady)
                    @if($isFreeWebBasic)
                        @if($freeDownloadClaimed)
                            <div class="mt-3 w-full py-2.5 rounded-lg bg-green-50 text-green-700 text-sm text-center font-semibold">
                                ✓ {{ __('ui.free_claimed') }}
                            </div>
                        @else
                            {{-- Campo email + botón gratis --}}
                            <div class="mt-3 space-y-2">
                                <label for="pp-free-email" class="sr-only">{{ __('ui.a11y_email_label') }}</label>
                                <input
                                    type="email"
                                    id="pp-free-email"
                                    autocomplete="email"
                                    wire:model="freeEmail"
                                    wire:keydown.enter="claimFreeDownload"
                                    placeholder="{{ __('ui.email_placeholder') }}"
                                    class="w-full px-3 py-2 rounded-lg border border-pmf-border text-pmf-text text-sm text-center focus:ring-green-500 focus:border-green-500"
                                >
                                @error('freeEmail') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                <button
                                    wire:click="claimFreeDownload"
                                    wire:loading.attr="disabled"
                                    class="w-full bg-green-600 text-white font-bold py-2.5 rounded-lg hover:bg-green-700 transition text-sm tracking-wide disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="claimFreeDownload">{{ __('ui.free_download_btn') }}</span>
                                    <span wire:loading wire:target="claimFreeDownload">{{ __('ui.upload_btn_loading') }}</span>
                                </button>
                                <p class="text-[10px] text-pmf-muted text-center">{{ __('ui.free_email_note') }}</p>
                            </div>
                        @endif
                    @elseif($product->highlight)
                        <button
                            wire:click="checkout('{{ $product->product_key }}')"
                            wire:loading.attr="disabled"
                            class="mt-3 w-full bg-burgundy text-white font-bold py-3 rounded-lg hover:bg-burgundy-dark transition text-sm tracking-wide disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="checkout('{{ $product->product_key }}')">{{ __('ui.btn_pack') }}</span>
                            <span wire:loading wire:target="checkout('{{ $product->product_key }}')">{{ __('ui.btn_pack_loading') }}</span>
                        </button>
                    @else
                        <button
                            wire:click="checkout('{{ $product->product_key }}')"
                            wire:loading.attr="disabled"
                            class="mt-3 w-full py-2.5 rounded-lg border border-burgundy text-burgundy font-semibold text-sm hover:bg-burgundy-soft transition disabled:opacity-50"
                        >
                            {{ __('ui.btn_choose') }}
                        </button>
                    @endif
                @else
                    <div class="mt-3 w-full py-2.5 rounded-lg bg-pmf-border/50 text-pmf-muted text-sm text-center">
                        {{ __('ui.preview_waiting') }}
                    </div>
                @endif
            </div>
            @endforeach

        @else
            {{-- UPSELL: SELECTOR DE ESTILOS EXTRA (dropdown compacto con búsqueda) --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-serif text-xl font-bold text-pmf-text">{{ __('ui.upsell_title') }}</h2>
                    <button wire:click="cancelUpsell" class="text-xs text-pmf-muted hover:text-burgundy">
                        {{ __('ui.upsell_cancel') }}
                    </button>
                </div>

                <div class="bg-burgundy-soft rounded-lg p-3 text-center">
                    <p class="font-serif text-sm text-burgundy font-semibold">
                        {{ __('ui.upsell_add_styles') }}
                    </p>
                    <p class="text-xs text-pmf-muted mt-1">
                        {{ __('ui.upsell_max', ['price' => $this->extraProduct?->formattedPrice() ?? '2,99€', 'max' => 5]) }}
                    </p>
                </div>

                {{-- Estilos seleccionados (chips) --}}
                @if(count($selectedExtraStyles) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($selectedExtraStyles as $styleKey)
                        @php
                            $styleObj = $this->availableStyles->firstWhere('style_key', $styleKey);
                            $chipLabel = $styleObj ? $styleObj->t('label') : $styleKey;
                        @endphp
                        <span class="inline-flex items-center gap-1 bg-burgundy/10 text-burgundy text-xs font-semibold px-2.5 py-1.5 rounded-full border border-burgundy/20">
                            {{ $chipLabel }}
                            <button wire:click="toggleExtraStyle('{{ $styleKey }}')" class="hover:text-burgundy-dark ml-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    @endforeach
                </div>
                @endif

                {{-- Dropdown con búsqueda (Alpine.js) --}}
                @if(count($selectedExtraStyles) < 5)
                <div x-data="{
                    open: false,
                    search: '',
                    get filtered() {
                        const q = this.search.toLowerCase();
                        return this.$refs.options?.querySelectorAll('[data-label]') || [];
                    },
                    filter() {
                        const q = this.search.toLowerCase();
                        document.querySelectorAll('[data-style-option]').forEach(el => {
                            el.style.display = el.dataset.label.toLowerCase().includes(q) ? '' : 'none';
                        });
                    }
                }" class="relative">
                    <button
                        type="button"
                        :aria-expanded="open ? 'true' : 'false'"
                        aria-haspopup="listbox"
                        @click="open = !open; $nextTick(() => { if(open) $refs.searchInput.focus() })"
                        class="w-full flex items-center justify-between bg-white border-2 border-pmf-border rounded-lg px-3 py-2.5 text-sm text-pmf-secondary hover:border-burgundy/50 transition"
                    >
                        <span>{{ __('ui.upsell_select_style') }}</span>
                        <svg class="w-4 h-4 text-pmf-muted transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-transition.opacity
                         class="absolute z-30 mt-1 w-full bg-white border border-pmf-border rounded-lg shadow-lg overflow-hidden">
                        {{-- Buscador --}}
                        <div class="p-2 border-b border-pmf-border">
                            <label for="pp-upsell-search" class="sr-only">{{ __('ui.a11y_search_styles') }}</label>
                            <input id="pp-upsell-search" x-ref="searchInput" x-model="search" @input="filter()" type="text"
                                   placeholder="{{ __('ui.upsell_search') }}"
                                   class="w-full text-sm border border-pmf-border rounded px-2.5 py-1.5 focus:outline-none focus:border-burgundy">
                        </div>
                        {{-- Lista de opciones --}}
                        <div class="max-h-52 overflow-y-auto">
                            @foreach($this->availableStyles as $style)
                                @if(!in_array($style->style_key, $selectedExtraStyles))
                                @php
                                    $label = $style->t('label');
                                @endphp
                                <button
                                    data-style-option data-label="{{ $label }}"
                                    wire:click="toggleExtraStyle('{{ $style->style_key }}')"
                                    @click="open = false; search = ''"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-pmf-text hover:bg-burgundy-soft transition text-left"
                                >
                                    @if($style->preview_image)
                                        <img src="{{ asset('images/styles/thumbs/' . $style->style_key . '.jpg') }}"
                                             alt="" class="w-8 h-8 rounded object-cover flex-shrink-0"
                                             onerror="this.style.display='none'">
                                    @else
                                        <span class="w-8 h-8 rounded bg-pmf-border/30 flex items-center justify-center text-sm flex-shrink-0">🎨</span>
                                    @endif
                                    <span class="truncate">{{ $label }}</span>
                                </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @else
                <p class="text-xs text-pmf-muted text-center italic">{{ __('ui.upsell_max_reached') }}</p>
                @endif

                {{-- Resumen precio --}}
                <div class="bg-white rounded-xl border border-pmf-border p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-pmf-secondary">Pack Completo</span>
                        <span class="font-semibold">9,99€</span>
                    </div>
                    @if(count($selectedExtraStyles) > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-pmf-secondary">{{ count($selectedExtraStyles) }}× {{ __('ui.upsell_extra_style') }}</span>
                        <span class="font-semibold">{{ number_format(count($selectedExtraStyles) * ($this->extraProduct?->priceCents() ?? 299) / 100, 2, ',', '.') }}€</span>
                    </div>
                    @endif
                    <div class="border-t border-pmf-border pt-2 flex justify-between">
                        <span class="font-serif font-bold text-pmf-text">Total</span>
                        <span class="font-serif text-xl font-bold text-burgundy">{{ number_format($this->upsellTotal / 100, 2, ',', '.') }}€</span>
                    </div>
                </div>

                {{-- CTA — wire:loading.attr es lo que impide que un doble clic
                     durante el redirect a Stripe abra dos sesiones de pago. --}}
                <button
                    wire:click="proceedToCheckout"
                    wire:loading.attr="disabled"
                    wire:target="proceedToCheckout"
                    class="w-full bg-burgundy text-white font-bold py-3.5 rounded-lg hover:bg-burgundy-dark transition text-sm tracking-wide disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="proceedToCheckout">{{ __('ui.upsell_proceed') }}</span>
                    <span wire:loading wire:target="proceedToCheckout">{{ __('ui.btn_pack_loading') }}</span>
                </button>

                <p class="text-center text-xs text-pmf-muted">
                    {{ __('ui.upsell_skip') }}
                    <button
                        wire:click="proceedToCheckout"
                        wire:loading.attr="disabled"
                        wire:target="proceedToCheckout"
                        class="text-burgundy font-semibold hover:underline disabled:opacity-50 disabled:cursor-not-allowed disabled:no-underline"
                    >
                        {{ __('ui.upsell_skip_link') }}
                    </button>
                </p>
            </div>
        @endif

        <div class="space-y-3 pt-4 text-xs text-pmf-muted">
            <p>🛡 {{ __('ui.trust_payment') }}</p>
            <p>📥 {{ __('ui.trust_download') }}</p>
            <p>🔒 {{ __('ui.preview_trust_secure') }}</p>
        </div>
    </div>
</div>
