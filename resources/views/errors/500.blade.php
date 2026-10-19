{{-- Hallmark · genre: editorial · family: contenido · macrostructure: Long Document · design-system: design.md · designed-as-app
     error de servidor --}}
@extends('layouts.app')
@section('hide-footer', true)

@section('content')
<section class="py-32 px-6 text-center">
    <div class="max-w-md mx-auto space-y-6">
        <div class="text-7xl">😓</div>
        <h1 class="font-serif text-5xl font-bold text-pmf-text">500</h1>
        <p class="font-serif text-lg text-pmf-secondary leading-relaxed">
            {{ __('ui.error_500_message') }}
        </p>
        <a href="{{ locale_route('landing') }}" class="inline-block bg-burgundy text-white font-bold px-6 py-3 rounded-lg hover:bg-burgundy-dark transition">
            {{ __('ui.btn_back_home') }}
        </a>
        <p class="text-xs text-pmf-muted">
            {{ __('ui.error_500_persist') }}
            <a href="mailto:{{ config('genpic.contact_email') }}" class="text-burgundy font-semibold">{{ config('genpic.contact_email') }}</a>
        </p>
    </div>
</section>
@endsection
