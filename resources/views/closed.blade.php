{{-- Hallmark · genre: editorial · family: contenido · macrostructure: Long Document · design-system: design.md · designed-as-app
     Pagina de cierre del servicio. La sirve el middleware SiteClosed con 410 en
     las rutas del funnel, y tambien la ruta publica /cierre desde el banner.
     Sin tarjetas, sin iconos, sin emoji: es prosa y una lista de enlaces. --}}
@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Carbon;

    $locale = app()->getLocale();

    $closingDate = config('genpic.closing.date');
    $closingDateLabel = $closingDate
        ? Carbon::parse($closingDate)->locale($locale)->isoFormat('LL')
        : null;

    // Una fecha de cierre por venir no se cuenta en pasado. El flag ya corta el
    // funnel; el lede solo dice desde cuando (o a partir de cuando) es oficial.
    $closingLedeKey = ($closingDate && Carbon::parse($closingDate)->isFuture())
        ? 'ui.closing_lede_future'
        : 'ui.closing_lede';

    // Fin del plazo de descarga. No es la fecha de cierre: cerrar las altas no
    // borra lo ya generado. Si no se configura, se deriva del TTL de sesion.
    $downloadsUntil = config('genpic.closing.downloads_until')
        ? Carbon::parse(config('genpic.closing.downloads_until'))
        : ($closingDate ? Carbon::parse($closingDate)->addDays((int) config('genpic.session_ttl_days', 14)) : null);

    // Un plazo vencido no se anuncia: se pasa al texto sin fecha.
    $downloadsUntilLabel = ($downloadsUntil && $downloadsUntil->isFuture())
        ? $downloadsUntil->locale($locale)->isoFormat('LL')
        : null;

    $repoUrl = config('genpic.closing.repo_url');
    $projects = config('genpic.closing.projects', []);
@endphp

<section class="bg-burgundy py-16 px-6">
    <div class="max-w-3xl mx-auto">
        <h1 class="font-serif text-4xl font-bold text-white tracking-tight [overflow-wrap:anywhere]">
            {{ __('ui.closing_title') }}
        </h1>
        @if($closingDateLabel)
            <p class="text-white/70 mt-3 text-sm">
                {{ __($closingLedeKey, ['date' => $closingDateLabel]) }}
            </p>
        @endif
    </div>
</section>

<section class="py-16 px-6">
    <div class="max-w-2xl mx-auto">
        <div class="space-y-10 text-pmf-secondary text-[15px] leading-relaxed">

            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.closing_h_what') }}</h2>
                <p class="mb-3">{{ __('ui.closing_what_1') }}</p>
                <p class="mb-3">{{ __('ui.closing_what_2') }}</p>
                <p>{{ __('ui.closing_what_3') }}</p>
            </div>

            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.closing_h_downloads') }}</h2>
                <p class="mb-4">
                    {{ $downloadsUntilLabel
                        ? __('ui.closing_downloads_1', ['date' => $downloadsUntilLabel])
                        : __('ui.closing_downloads_1_nodate') }}
                </p>
                <a href="{{ locale_route('downloads.form') }}"
                   class="inline-block bg-burgundy text-white font-bold px-8 py-4 rounded-lg whitespace-nowrap hover:bg-burgundy-dark transition-colors duration-200">
                    {{ __('ui.closing_downloads_cta') }}
                </a>
            </div>

            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.closing_h_code') }}</h2>
                <p class="mb-3">{{ __('ui.closing_code_1') }}</p>
                @if($repoUrl)
                    <p>
                        <a href="{{ $repoUrl }}" rel="noopener"
                           class="text-burgundy font-semibold underline underline-offset-4 hover:text-burgundy-dark transition-colors duration-200">
                            {{ __('ui.closing_code_cta') }}
                        </a>
                    </p>
                @else
                    <p class="text-pmf-muted">{{ __('ui.closing_code_soon') }}</p>
                @endif
            </div>

            @if($projects)
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.closing_h_projects') }}</h2>
                <p class="mb-5">{{ __('ui.closing_projects_intro') }}</p>
                <ul class="space-y-3">
                    @foreach($projects as $project)
                        <li>
                            <a href="https://{{ $project['domain'] }}" rel="noopener"
                               class="text-burgundy font-semibold underline underline-offset-4 hover:text-burgundy-dark transition-colors duration-200">
                                {{ $project['domain'] }}
                            </a>
                            <span class="text-pmf-secondary"> — {{ __('ui.closing_project_'.$project['key']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.closing_h_thanks') }}</h2>
                <p>{{ __('ui.closing_thanks_1') }}</p>
            </div>

            <div class="border-t border-pmf-border pt-8">
                <a href="{{ $locale === 'es' ? '/' : '/' . $locale }}"
                   class="text-burgundy font-semibold underline underline-offset-4 hover:text-burgundy-dark transition-colors duration-200">
                    {{ __('ui.closing_back') }}
                </a>
            </div>

        </div>
    </div>
</section>
@endsection
