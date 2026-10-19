{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     peticion de acceso --}}
@extends('layouts.app')

@section('content')
<section class="py-20 px-6">
    <div class="max-w-md mx-auto text-center space-y-8">
        <div class="w-20 h-20 rounded-full bg-burgundy-soft flex items-center justify-center mx-auto text-3xl">📥</div>

        <h1 class="font-serif text-3xl font-bold text-pmf-text">
            {{ __('ui.downloads_title') }}
        </h1>
        <p class="font-serif text-pmf-secondary leading-relaxed">
            {{ __('ui.downloads_desc') }}
        </p>

        @if($errors->has('expired'))
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
            {{ $errors->first('expired') }}
        </div>
        @endif

        <form action="{{ locale_route('downloads.send-pin') }}" method="POST" class="space-y-4">
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
                {{ __('ui.downloads_send_code') }}
            </button>
        </form>

        <p class="text-xs text-pmf-muted">
            🔒 {{ __('ui.downloads_security_note') }}
        </p>
    </div>
</section>
@endsection
