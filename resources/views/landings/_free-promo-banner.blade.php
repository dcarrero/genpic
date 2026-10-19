{{-- Hallmark · component: promo-banner · design-system: design.md · designed-as-app
     Los 3 hex inline eran tokens existentes (burgundy-dark / champagne / copper):
     traducidos a clases. Emoji retirado — era el único del conjunto y vivía en el
     partial que design.md obliga a incluir en las 10 landings.
     Contraste: champagne 11.39:1 · copper 4.75:1 sobre burgundy-dark. --}}
@if(config('genpic.free_web_basic_enabled'))
<div class="text-center py-2.5 px-4 bg-burgundy-dark text-champagne">
    <p class="text-sm font-semibold">
        <s>{{ __('ui.pricing_title') }}</s>
        <span class="text-copper"> — {{ __('ui.free_promo_banner_text') }}</span>
    </p>
</div>
@endif
