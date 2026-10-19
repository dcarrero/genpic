{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     contenedor del preview --}}
@extends('layouts.app')
@section('hide-footer', true)

@section('stepper')
<div class="flex items-center gap-1 sm:gap-2">
    @foreach([['1', __('ui.step_style'), true], ['2', __('ui.step_photo'), true], ['3', __('ui.step_result'), true], ['4', __('ui.step_download'), false]] as [$num, $label, $active])
        @if(!$loop->first)<div class="w-4 sm:w-8 h-0.5 {{ $active ? 'bg-white/50' : 'bg-white/20' }}"></div>@endif
        <div class="flex items-center gap-1">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $active ? 'bg-white text-burgundy' : 'bg-white/20 text-white/70' }}">{{ $num }}</div>
            <span class="hidden sm:inline text-xs font-semibold {{ $active ? 'text-white' : 'text-white/50' }} tracking-wide">{{ $label }}</span>
        </div>
    @endforeach
</div>
@endsection

@section('content')
<section class="py-12 px-6">
    <div class="max-w-6xl mx-auto">
        <livewire:preview-panel :session="$session" />
    </div>
</section>

@push('scripts')
<script>
if (typeof gtag === 'function') {
    gtag('event', 'view_result', { style: @json($session->style_key) });
}
</script>
@endpush
@endsection
