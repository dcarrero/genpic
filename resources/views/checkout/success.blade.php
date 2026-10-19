{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     confirmacion --}}
@extends('layouts.app')

@php
    // Todo en EUR — Stripe Adaptive Pricing convierte al pagar
@endphp

@section('content')
<section class="py-20 px-6 text-center">
    <div class="max-w-lg mx-auto space-y-6">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto text-2xl">✓</div>
        <h1 class="font-serif text-3xl font-bold text-pmf-text">{{ __('ui.checkout_success_title') }}</h1>
        <p class="font-serif text-pmf-secondary leading-relaxed">{{ __('ui.checkout_success_desc') }}</p>

        @if($order)
        <div class="bg-white rounded-xl border border-pmf-border p-5 text-left space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-pmf-muted">{{ __('ui.checkout_order') }}</span>
                <span class="font-mono text-xs text-pmf-text">{{ $order->uuid }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-pmf-muted">{{ __('ui.checkout_status') }}</span>
                <span class="font-semibold {{ $order->status === 'ready' ? 'text-green-600' : 'text-burgundy' }}">
                    @if($order->status === 'ready')
                        ✅ {{ __('ui.checkout_status_ready') }}
                    @elseif($order->status === 'processing')
                        ⏳ {{ __('ui.checkout_status_processing') }}
                    @else
                        ✅ {{ __('ui.checkout_status_paid') }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-pmf-muted">Total</span>
                <span class="font-bold text-pmf-text">{{ number_format($order->total_cents / 100, 2, ',', '.') }}€</span>
            </div>

            @if($order->download_ready)
            <div class="pt-3 border-t border-pmf-border space-y-3">
                <p class="text-xs text-green-600 font-semibold">📥 {{ __('ui.checkout_files_ready') }}</p>

                {{-- Descargas individuales --}}
                @foreach($order->items as $item)
                    @if($item->export_paths)
                        @foreach($item->export_paths as $formatKey => $path)
                        <a href="{{ URL::temporarySignedRoute('secure.export', now()->addHours(72), ['order' => $order->uuid, 'format' => $formatKey]) }}"
                           class="flex items-center justify-between p-3 bg-champagne rounded-lg hover:bg-champagne-dark transition">
                            <div>
                                <span class="text-sm font-semibold text-pmf-text">{{ str_replace('_', ' ', ucfirst($formatKey)) }}</span>
                                <span class="text-[10px] text-pmf-muted ml-2">{{ strtoupper(pathinfo($path, PATHINFO_EXTENSION)) }}</span>
                            </div>
                            <span class="bg-burgundy text-white text-xs font-bold px-3 py-1 rounded">↓</span>
                        </a>
                        @endforeach

                        {{-- ZIP si hay más de 1 formato --}}
                        @if(count($item->export_paths) > 1)
                        <a href="{{ URL::temporarySignedRoute('secure.zip', now()->addHours(72), ['order' => $order->uuid]) }}"
                           class="flex items-center justify-between p-3 bg-burgundy-soft rounded-lg hover:bg-burgundy/10 transition">
                            <div>
                                <span class="text-sm font-bold text-burgundy">📦 {{ __('ui.checkout_download_zip') }}</span>
                                <span class="text-[10px] text-pmf-secondary ml-2">{{ count($item->export_paths) }} {{ __('ui.checkout_files_count') }}</span>
                            </div>
                            <span class="bg-burgundy text-white text-xs font-bold px-3 py-1 rounded">ZIP ↓</span>
                        </a>
                        @endif
                    @endif
                @endforeach
            </div>
            @else
            <div class="pt-3 border-t border-pmf-border">
                <p class="text-xs text-pmf-muted">⏳ {{ __('ui.checkout_files_preparing') }}</p>
                <p class="text-[10px] text-pmf-muted mt-1">{{ __('ui.checkout_files_refresh') }}</p>
            </div>
            @endif
        </div>
        @endif

        <p class="text-sm text-pmf-muted">{{ __('ui.checkout_success_ttl') }}</p>
        <a href="{{ locale_route('landing') }}" class="inline-block bg-burgundy text-white font-semibold px-6 py-3 rounded-lg hover:bg-burgundy-dark transition">
            {{ __('ui.btn_back_home') }}
        </a>
    </div>
</section>

{{-- GA4: evento purchase (solo si order pagada y analytics aceptados) --}}
@if($order && $order->isPaid())
@push('scripts')
<script>
(function(){
    try {
        // GA4 funciona en modo cookieless — siempre registrar ventas
        if (sessionStorage.getItem('ga_purchase_{{ $order->uuid }}')) return;

        if (typeof gtag === 'function') {
            gtag('event', 'purchase', {
                transaction_id: '{{ $order->uuid }}',
                value: {{ $order->total_cents / 100 }},
                currency: '{{ strtoupper($order->currency ?? "EUR") }}',
                items: [
                    @foreach($order->items as $item)
                    {
                        item_id: '{{ $item->product?->product_key ?? "unknown" }}',
                        item_name: '{{ $item->product?->t('name') ?? "Retrato" }}',
                        price: {{ ($item->total_cents ?? 0) / 100 }},
                        quantity: {{ $item->quantity ?? 1 }}
                    },
                    @endforeach
                ]
            });
            sessionStorage.setItem('ga_purchase_{{ $order->uuid }}', '1');
        }
    } catch(e) {}
})();
</script>
@endpush
@endif
@endsection
