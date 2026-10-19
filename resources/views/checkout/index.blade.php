{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     formulario de pago --}}
@extends('layouts.app')
@section('hide-footer', true)

@php
    $locale = app()->getLocale();
    $productKey = request('product');
    $product = ($productKey ? \App\Services\CachedQueries::productByKey($productKey) : null)
        ?? \App\Services\CachedQueries::activeProducts()->first();
    $name = $product->t('name');
    $desc = $product->t('description');
    $price = $product->formattedPrice();
    $generation = $session->latestGeneration;
    $hasWatermark = $generation && $generation->watermarked_image_path;
    $userEmail = $session->user?->email;
    $hasRealEmail = $userEmail && !str_starts_with($userEmail, 'pending_');
@endphp

@section('content')
<section class="py-10 px-6">
    <div class="max-w-lg mx-auto">
        {{-- role=alert: es el error que impide pagar; el lector debe anunciarlo. --}}
        @if($errors->has('session') || $errors->has('stripe'))
        <div role="alert" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 text-center">
            {{ $errors->first('session') ?: $errors->first('stripe') }}
        </div>
        @endif

        <h1 class="font-serif text-2xl font-bold text-pmf-text mb-6 text-center">
            {{ __('ui.checkout_title') }}
        </h1>

        <div class="bg-white rounded-2xl border border-pmf-border shadow-sm overflow-hidden">
            {{-- Si es Pack Completo: mostrar todos los packs tachados --}}
            @if($product->product_key === 'pack_completo')
            @php
                $otherProducts = \App\Services\CachedQueries::activeProducts()->where('product_key', '!=', 'pack_completo');
            @endphp
            <div class="p-5 border-b border-pmf-border space-y-3">
                @foreach($otherProducts as $other)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-green-600 text-sm">✓</span>
                        <span class="text-sm text-pmf-secondary">{{ $other->t('name') }}</span>
                    </div>
                    <span class="text-sm text-pmf-text" style="text-decoration: line-through; text-decoration-color: #7A2D3B; text-decoration-thickness: 2px;">{{ $other->formattedPrice() }}</span>
                </div>
                @endforeach
                <div class="flex items-center justify-between pt-2 border-t border-pmf-border">
                    <span class="text-sm font-bold text-pmf-text">{{ __('ui.checkout_all_included') }}</span>
                </div>
            </div>
            @endif

            {{-- Producto seleccionado + Preview mini --}}
            <div class="flex items-center gap-4 p-5 border-b border-pmf-border {{ $product->product_key === 'pack_completo' ? 'bg-burgundy-soft/30' : '' }}">
                @if($hasWatermark)
                <img src="{{ route('secure.watermarked', $session) }}" alt="Preview" class="w-16 h-16 rounded-lg object-cover flex-shrink-0 border border-pmf-border">
                @endif
                <div class="flex-1 min-w-0">
                    <h3 class="font-serif text-base font-bold text-pmf-text">{{ $name }}</h3>
                    <p class="text-xs text-pmf-secondary mt-0.5 line-clamp-2">{{ $desc }}</p>
                </div>
                <p class="font-serif text-xl font-bold text-burgundy flex-shrink-0">{{ $price }}</p>
            </div>

            {{-- Formatos incluidos --}}
            <div class="px-5 py-3 bg-champagne border-b border-pmf-border">
                <div class="flex flex-wrap gap-x-4 gap-y-0.5">
                    @foreach($product->formats as $format)
                    <span class="text-[11px] text-pmf-secondary">
                        ✓ {{ $format->t('label') }}
                        @if($format->max_dimension_px)({{ $format->max_dimension_px }}px)@endif
                        @if($format->width_px)({{ $format->width_px }}×{{ $format->height_px }})@endif
                    </span>
                    @endforeach
                </div>
            </div>

            {{-- Nota cupón: se aplica en Stripe --}}
            <div class="px-5 py-3 border-b border-pmf-border">
                <p class="text-[11px] text-pmf-muted">🏷️ {{ __('ui.coupon_stripe_note') }}</p>
            </div>

            {{-- Email + Pagar --}}
            {{-- submitting bloquea el reenvío: el redirect a Stripe tarda, y sin
                 esto un doble clic crea dos sesiones de pago. --}}
            <form
                action="{{ locale_route('checkout.session') }}"
                method="POST"
                class="p-5 space-y-4"
                x-data="{ submitting: false }"
                x-on:submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
            >
                @csrf
                <input type="hidden" name="session_uuid" value="{{ $session->uuid }}">
                <input type="hidden" name="product_key" value="{{ $product->product_key }}">

                <div>
                    <label for="checkout-email" class="text-xs font-semibold text-pmf-text block mb-1">
                        {{ __('ui.checkout_email_label') }}
                    </label>
                    <input
                        type="email"
                        id="checkout-email"
                        name="email"
                        value="{{ $hasRealEmail ? $userEmail : '' }}"
                        placeholder="{{ __('ui.email_placeholder') }}"
                        required
                        class="w-full px-4 py-2.5 rounded-lg bg-champagne border border-pmf-border text-pmf-text font-serif text-sm focus:ring-burgundy focus:border-burgundy"
                    >
                </div>

                <button
                    type="submit"
                    x-bind:disabled="submitting"
                    class="w-full bg-burgundy text-white font-bold py-3.5 rounded-lg hover:bg-burgundy-dark transition tracking-wide text-base disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span x-show="!submitting">{{ __('ui.checkout_pay') }} {{ $price }} →</span>
                    <span x-show="submitting" x-cloak>{{ __('ui.btn_pack_loading') }}</span>
                </button>

                <div class="flex justify-center gap-4 text-[10px] text-pmf-muted">
                    <span>🔒 {{ __('ui.checkout_secure') }}</span>
                    <span>📥 {{ __('ui.checkout_instant') }}</span>
                </div>
            </form>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ locale_route('create.preview', $session) }}" class="text-sm text-burgundy font-semibold hover:underline">
                ← {{ __('ui.checkout_back') }}
            </a>
        </div>
    </div>
</section>

@push('scripts')
<script>
if (typeof gtag === 'function') {
    gtag('event', 'begin_checkout', {
        value: {{ $product->priceCents() / 100 }},
        currency: 'EUR',
        items: [{ item_id: @json($product->product_key), item_name: @json($name), price: {{ $product->priceCents() / 100 }} }]
    });
}
</script>
@endpush
@endsection
