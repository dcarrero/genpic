{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     lista de descargas --}}
@extends('layouts.app')

@php $locale = app()->getLocale(); @endphp

@section('content')
<section class="py-16 px-6">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="font-serif text-3xl font-bold text-pmf-text">{{ __('ui.downloads_list_title') }}</h1>
            <p class="text-sm text-pmf-muted mt-2">{{ $user->email }}</p>
        </div>

        @if($sessions->isEmpty())
        <div class="text-center py-16">
            <p class="font-serif text-lg text-pmf-muted">{{ __('ui.downloads_empty') }}</p>
            <a href="{{ locale_route('create.index') }}" class="inline-block mt-4 bg-burgundy text-white font-bold px-6 py-3 rounded-lg hover:bg-burgundy-dark transition">
                {{ __('ui.nav_cta') }} →
            </a>
        </div>
        @else
        <div class="space-y-6">
            @foreach($sessions as $session)
            @php
                $generation = $session->latestGeneration;
                $styleKey = $session->style_key;
                $styleLabel = $session->artisticStyle
                    ? $session->artisticStyle->t('label')
                    : $styleKey;
                $orders = $session->orders;
                $paidOrder = $orders->firstWhere('status', 'ready') ?? $orders->firstWhere('status', 'paid');
                $hasPaid = $paidOrder && in_array($paidOrder->status, ['paid', 'ready']);
                $hasWatermark = $generation && $generation->watermarked_image_path;
                $expiresAt = $session->expires_at;
                $daysLeft = $expiresAt ? (int) now()->diffInDays($expiresAt, false) : 0;
                $isExpiringSoon = $daysLeft <= 3 && $daysLeft >= 0;
            @endphp
            <div class="bg-white rounded-2xl border border-pmf-border shadow-sm overflow-hidden">
                {{-- Header con preview + info --}}
                <div class="flex items-center gap-4 p-5">
                    @if($hasWatermark)
                    <img src="{{ route('secure.watermarked', $session) }}" alt="{{ $styleLabel }}" class="w-20 h-20 rounded-lg object-cover flex-shrink-0 border border-pmf-border">
                    @else
                    <div class="w-20 h-20 rounded-lg bg-champagne-dark flex items-center justify-center flex-shrink-0 text-2xl">🎨</div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <h3 class="font-serif text-base font-bold text-pmf-text">{{ $styleLabel }}</h3>
                        <p class="text-xs text-pmf-muted">{{ $session->created_at->translatedFormat('d M Y, H:i') }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                            @if($hasPaid)
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                                ✓ {{ __('ui.downloads_status_paid') }}
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                                {{ __('ui.downloads_status_preview') }}
                            </span>
                            @endif

                            {{-- Expiración --}}
                            @if($daysLeft > 0)
                            <span class="text-[10px] font-semibold {{ $isExpiringSoon ? 'text-red-600' : 'text-pmf-muted' }}">
                                ⏱ {{ __('ui.downloads_expires', ['days' => $daysLeft]) }}
                            </span>
                            @elseif($daysLeft <= 0 && !$session->purged_at)
                            <span class="text-[10px] font-bold text-red-600">
                                {{ __('ui.downloads_expiring_today') }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Aviso de expiración --}}
                @if($daysLeft <= 5 && $daysLeft > 0)
                <div class="mx-5 mb-3 p-3 rounded-lg {{ $isExpiringSoon ? 'bg-red-50 border border-red-200' : 'bg-amber-50 border border-amber-200' }}">
                    <p class="text-xs {{ $isExpiringSoon ? 'text-red-700' : 'text-amber-700' }}">
                        ⚠ {{ __('ui.downloads_expiry_warning', ['date' => $expiresAt->translatedFormat('d \d\e F \d\e Y')]) }}
                    </p>
                </div>
                @endif

                {{-- Downloads si pagado --}}
                @if($hasPaid && $paidOrder->download_ready)
                <div class="px-5 pb-5 space-y-2">
                    @foreach($paidOrder->items as $item)
                        @if($item->export_paths)
                            @foreach($item->export_paths as $formatKey => $path)
                            <a href="{{ URL::temporarySignedRoute('secure.export', now()->addHours(72), ['order' => $paidOrder->uuid, 'format' => $formatKey]) }}"
                               class="flex items-center justify-between p-3 bg-champagne rounded-lg hover:bg-champagne-dark transition">
                                <div>
                                    <span class="text-sm font-semibold text-pmf-text">{{ str_replace('_', ' ', ucfirst($formatKey)) }}</span>
                                    <span class="text-[10px] text-pmf-muted ml-2">{{ strtoupper(pathinfo($path, PATHINFO_EXTENSION)) }}</span>
                                </div>
                                <span class="bg-burgundy text-white text-xs font-bold px-3 py-1 rounded">↓</span>
                            </a>
                            @endforeach

                            @if(count($item->export_paths) > 1)
                            <a href="{{ URL::temporarySignedRoute('secure.zip', now()->addHours(72), ['order' => $paidOrder->uuid]) }}"
                               class="flex items-center justify-between p-3 bg-burgundy-soft rounded-lg hover:bg-burgundy/10 transition mt-1">
                                <span class="text-sm font-bold text-burgundy">📦 {{ __('ui.checkout_download_zip') }}</span>
                                <span class="bg-burgundy text-white text-xs font-bold px-3 py-1 rounded">ZIP ↓</span>
                            </a>
                            @endif
                        @endif
                    @endforeach

                    {{-- Link a factura Stripe --}}
                    @if($paidOrder->stripe_checkout_id)
                    <div class="pt-2 text-center">
                        <p class="text-[10px] text-pmf-muted">
                            {{ __('ui.downloads_order_ref', ['uuid' => substr($paidOrder->uuid, 0, 8)]) }}
                        </p>
                    </div>
                    @endif
                </div>

                @elseif($hasPaid && !$paidOrder->download_ready)
                {{-- Pagado pero archivos aún preparándose --}}
                <div class="px-5 pb-5">
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-center">
                        <p class="text-xs text-amber-700">⏳ {{ __('ui.checkout_files_preparing') }}</p>
                    </div>
                </div>

                @else
                {{-- No pagado: CTA para comprar --}}
                <div class="px-5 pb-5 space-y-3">
                    <a href="{{ locale_route('create.preview', $session) }}"
                       class="block w-full text-center bg-burgundy text-white font-bold py-3 rounded-lg hover:bg-burgundy-dark transition text-sm">
                        {{ __('ui.downloads_buy_cta') }}
                    </a>
                    <p class="text-[10px] text-pmf-muted text-center">
                        {{ __('ui.downloads_preview_note') }}
                    </p>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Nota general de expiración --}}
        <div class="mt-8 p-4 bg-champagne rounded-xl text-center">
            <p class="text-xs text-pmf-secondary leading-relaxed">
                🔒 {{ __('ui.downloads_retention_note') }}
            </p>
        </div>
        @endif

        <div class="text-center mt-6">
            <a href="{{ locale_route('create.index') }}" class="inline-block bg-burgundy text-white font-semibold px-5 py-2.5 rounded-lg hover:bg-burgundy-dark transition text-sm mr-3">
                {{ __('ui.nav_cta') }} →
            </a>
            <a href="{{ locale_route('landing') }}" class="text-burgundy font-semibold text-sm hover:underline">
                {{ __('ui.btn_back_home') }}
            </a>
        </div>
    </div>
</section>
@endsection
