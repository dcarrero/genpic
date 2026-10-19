{{-- Hallmark · genre: editorial · family: contenido · macrostructure: Long Document · design-system: design.md · designed-as-app
     expirado --}}
@extends('layouts.app')
@section('hide-footer', true)

@section('content')
<section class="py-32 px-6 text-center">
    <div class="max-w-md mx-auto space-y-6">
        <div class="text-7xl">⏰</div>
        <h1 class="font-serif text-5xl font-bold text-pmf-text">{{ __('ui.expired') }}</h1>
        <p class="font-serif text-lg text-pmf-secondary leading-relaxed">
            {{ app()->getLocale() === 'en'
                ? 'This portrait has expired and has been permanently deleted from our servers. Portraits are kept for 14 days.'
                : 'Este retrato ha expirado y se ha eliminado permanentemente de nuestros servidores. Los retratos se conservan 14 días.' }}
        </p>
        <a href="{{ locale_route('create.index') }}" class="inline-block bg-burgundy text-white font-bold px-6 py-3 rounded-lg hover:bg-burgundy-dark transition">
            {{ __('ui.nav_cta') }} →
        </a>
    </div>
</section>
@endsection
