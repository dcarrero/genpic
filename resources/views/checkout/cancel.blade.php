{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     pago cancelado --}}
@extends('layouts.app')

@section('content')
<section class="py-20 px-6 text-center">
    <div class="max-w-lg mx-auto space-y-6">
        <div class="w-16 h-16 rounded-full bg-burgundy-soft flex items-center justify-center mx-auto text-2xl">🎨</div>
        <h1 class="font-serif text-3xl font-bold text-pmf-text">{{ __('ui.checkout_cancel_title') }}</h1>
        <p class="font-serif text-pmf-secondary leading-relaxed">{{ __('ui.checkout_cancel_desc') }}</p>
        @if($order)
            <a href="{{ locale_route('create.preview', $order->session) }}" class="inline-block bg-burgundy text-white font-semibold px-6 py-3 rounded-lg hover:bg-burgundy-dark transition">
                {{ __('ui.btn_view_portrait') }}
            </a>
        @else
            <a href="{{ locale_route('landing') }}" class="inline-block bg-burgundy text-white font-semibold px-6 py-3 rounded-lg hover:bg-burgundy-dark transition">
                {{ __('ui.btn_back_home') }}
            </a>
        @endif
    </div>
</section>
@endsection
