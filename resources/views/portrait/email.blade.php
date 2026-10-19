{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     acceso al retrato --}}
@extends('layouts.app')
@section('hide-footer', true)

@section('content')
<section class="py-20 px-6">
    <div class="max-w-md mx-auto text-center space-y-8">
        <div class="w-20 h-20 rounded-full bg-burgundy-soft flex items-center justify-center mx-auto text-3xl">🎨</div>

        <h1 class="font-serif text-3xl font-bold text-pmf-text">
            {{ __('ui.portrait_access_title') }}
        </h1>
        <p class="font-serif text-pmf-secondary leading-relaxed">
            {{ __('ui.portrait_access_desc') }}
        </p>

        <form action="{{ locale_route('portrait.send-pin', $session) }}" method="POST" class="space-y-4">
            @csrf
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="{{ __('ui.email_placeholder') }}"
                required
                autofocus
                class="w-full px-4 py-3 rounded-lg bg-white border border-pmf-border text-pmf-text font-serif text-center focus:ring-burgundy focus:border-burgundy"
            >
            @error('email')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full bg-burgundy text-white font-bold py-3.5 rounded-lg hover:bg-burgundy-dark transition tracking-wide">
                {{ __('ui.portrait_send_code') }}
            </button>
        </form>

        <p class="text-xs text-pmf-muted">
            🔒 {{ __('ui.portrait_email_protected') }}
        </p>
    </div>
</section>
@endsection
