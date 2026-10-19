{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     baja de emails --}}
@extends('layouts.app')
@section('hide-footer', true)

@section('content')
<section class="py-20 px-6">
    <div class="max-w-md mx-auto text-center space-y-6">
        <h1 class="font-serif text-2xl font-bold text-pmf-text">
            {{ $processed ?? false ? ($action === 'unsubscribe' ? __('ui.unsub_done_title') : __('ui.unsub_resubscribed_title')) : __('ui.unsub_title') }}
        </h1>

        @if($processed ?? false)
            @if($action === 'unsubscribe')
            <p class="text-pmf-secondary">{{ __('ui.unsub_done_desc') }}</p>
            @else
            <p class="text-pmf-secondary">{{ __('ui.unsub_resubscribed_desc') }}</p>
            @endif
        @else
            <p class="text-pmf-secondary">{{ __('ui.unsub_desc') }}</p>

            @if($error ?? false)
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ $error }}</div>
            @endif

            <form method="POST" action="{{ route('facecounsus.process') }}" class="space-y-4">
                @csrf
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="{{ __('ui.email_placeholder') }}"
                    class="w-full px-4 py-3 rounded-lg border border-pmf-border text-pmf-text font-serif text-center focus:ring-burgundy focus:border-burgundy"
                >
                <button type="submit" class="w-full bg-burgundy text-white font-bold py-3 rounded-lg hover:bg-burgundy-dark transition">
                    {{ __('ui.unsub_btn') }}
                </button>
            </form>

            <p class="text-xs text-pmf-muted">{{ __('ui.unsub_faceco_note') }}</p>
        @endif
    </div>
</section>
@endsection
