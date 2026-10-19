{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     verificacion PIN --}}
@extends('layouts.app')
@section('hide-footer', true)

@section('content')
<section class="py-20 px-6">
    <div class="max-w-md mx-auto text-center space-y-8">
        <div class="w-20 h-20 rounded-full bg-burgundy-soft flex items-center justify-center mx-auto text-3xl">🔐</div>

        <h1 class="font-serif text-3xl font-bold text-pmf-text">
            {{ __('ui.portrait_enter_code') }}
        </h1>

        @if($pin_sent)
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-sm text-green-800 font-semibold">
                📧 {{ __('ui.portrait_code_sent', ['email' => $email]) }}
            </p>
            <p class="text-xs text-green-700 mt-1">
                {{ __('ui.portrait_code_check') }}
            </p>
        </div>
        @endif

        <form action="{{ locale_route('portrait.check-pin', $session) }}" method="POST" class="space-y-4">
            @csrf
            <input
                type="text"
                name="pin"
                maxlength="6"
                placeholder="000000"
                required
                autofocus
                inputmode="numeric"
                pattern="[0-9]{6}"
                class="w-full px-4 py-4 rounded-lg bg-white border border-pmf-border text-pmf-text font-mono text-3xl text-center tracking-[0.5em] focus:ring-burgundy focus:border-burgundy"
            >
            @error('pin')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full bg-burgundy text-white font-bold py-3.5 rounded-lg hover:bg-burgundy-dark transition tracking-wide">
                {{ __('ui.portrait_verify_btn') }}
            </button>
        </form>

        <div class="text-xs text-pmf-muted space-y-2">
            <p>{{ __('ui.portrait_no_code') }}</p>
            <a href="{{ locale_route('portrait.access', $session) }}" class="text-burgundy font-semibold hover:underline">
                {{ __('ui.portrait_new_code') }}
            </a>
        </div>
    </div>
</section>
@endsection
