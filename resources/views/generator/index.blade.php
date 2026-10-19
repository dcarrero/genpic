{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     contenedor del funnel --}}
@extends('layouts.app')
@section('hide-footer', true)

@section('stepper')
<div class="flex items-center gap-1 sm:gap-2">
    @foreach([['1', __('ui.step_style'), true], ['2', __('ui.step_photo'), true], ['3', __('ui.step_result'), false], ['4', __('ui.step_download'), false]] as [$num, $label, $active])
        @if(!$loop->first)<div class="w-4 sm:w-8 h-0.5 {{ $active ? 'bg-white/50' : 'bg-white/20' }}"></div>@endif
        <div class="flex items-center gap-1">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $active ? 'bg-white text-burgundy' : 'bg-white/20 text-white/70' }}">{{ $num }}</div>
            <span class="hidden sm:inline text-xs font-semibold {{ $active ? 'text-white' : 'text-white/50' }} tracking-wide">{{ $label }}</span>
        </div>
    @endforeach
</div>
@endsection

@php
    $requestedStyle = request('style');
    $isRandom = !$requestedStyle;
    if ($isRandom) {
        $randomStyle = $styles->random();
        $requestedStyle = $randomStyle->style_key;
    }
@endphp

@section('content')
<section class="py-8 px-6">
    <div class="max-w-3xl mx-auto mb-8" x-data="{ hidden: false }" x-on:generation-started.window="hidden = true" x-show="!hidden" x-transition>
        @if($isRandom)
        <div class="text-center mb-4">
            <p class="text-sm text-pmf-secondary">
                ✨ {{ __('ui.style_random_suggestion') }}
            </p>
        </div>
        @endif
        <livewire:style-selector :style="$requestedStyle" />
    </div>
    <div class="max-w-2xl mx-auto">
        <livewire:photo-uploader :style="$requestedStyle" />
    </div>
</section>

{{-- GA4 funnel events --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof gtag !== 'function') return;
    document.addEventListener('style-selected', function(e) {
        gtag('event', 'select_style', { style: e.detail.styleKey || '' });
    });
    Livewire.on('generation-started', function() {
        gtag('event', 'generation_started');
    });
});
</script>
@endpush
@endsection
