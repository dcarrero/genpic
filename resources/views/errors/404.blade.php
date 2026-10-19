{{-- Hallmark · genre: editorial · family: contenido · macrostructure: Long Document · design-system: design.md · designed-as-app
     no encontrado --}}
@extends('layouts.app')
@section('hide-footer', true)

@section('content')
<section class="py-32 px-6 text-center">
    <div class="max-w-md mx-auto space-y-6">
        <div class="text-7xl">🎨</div>
        <h1 class="font-serif text-5xl font-bold text-pmf-text">404</h1>
        <p class="font-serif text-lg text-pmf-secondary leading-relaxed">
            {{ app()->getLocale() === 'en'
                ? 'This page doesn\'t exist. Maybe the portrait you\'re looking for has expired or the link is wrong.'
                : 'Esta página no existe. Puede que el retrato que buscas haya expirado o que el enlace sea incorrecto.' }}
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ locale_route('landing') }}" class="bg-burgundy text-white font-bold px-6 py-3 rounded-lg hover:bg-burgundy-dark transition">
                {{ __('ui.btn_back_home') }}
            </a>
            <a href="{{ locale_route('downloads.form') }}" class="text-burgundy font-semibold hover:underline">
                {{ __('ui.footer_downloads') }}
            </a>
        </div>
    </div>
</section>
@endsection
