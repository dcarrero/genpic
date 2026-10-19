{{-- Hallmark · component: closing-banner · design-system: design.md · designed-as-app
     Aviso permanente de cierre. Superficie plana pmf-dark; champagne sobre
     pmf-dark cumple AA con holgura. Sin emoji, como manda design.md. --}}
@if(config('genpic.closing.enabled'))
<div class="bg-pmf-dark text-champagne px-4 py-2.5" role="status">
    <p class="max-w-7xl mx-auto text-center text-sm">
        <span class="font-semibold">{{ __('ui.closing_banner_text') }}</span>
        <a href="{{ locale_route('closing') }}"
           class="ml-2 inline-block underline underline-offset-4 text-champagne hover:text-white transition-colors duration-200">
            {{ __('ui.closing_banner_link') }}
        </a>
    </p>
</div>
@endif
