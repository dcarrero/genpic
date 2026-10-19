{{-- Hallmark · genre: editorial · family: contenido · macrostructure: Long Document · design-system: design.md · designed-as-app
     terminos --}}
@extends('layouts.app')

@section('content')
<section class="bg-burgundy py-16 px-6">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="font-serif text-4xl font-bold text-white tracking-tight">
            {{ __('ui.legal_terms_title') }}
        </h1>
        <p class="text-white/70 mt-2 text-sm">
            {{ __('ui.legal_updated') }}
        </p>
    </div>
</section>

<section class="py-16 px-6">
    <div class="max-w-3xl mx-auto [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:mb-1.5">
        <div class="space-y-10 text-pmf-secondary text-[15px] leading-relaxed">

            {{-- Intro --}}
            <p>{{ __('ui.legal_terms_intro') }}</p>

            {{-- 1. Información del prestador --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_1_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_1_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_terms_1_name') }}</li>
                    <li>{{ __('ui.legal_terms_1_cif') }}</li>
                    <li>{{ __('ui.legal_terms_1_address') }}</li>
                    <li>{{ __('ui.legal_terms_1_email') }}</li>
                    <li>{{ __('ui.legal_terms_1_web') }}</li>
                </ul>
            </div>

            {{-- 2. Objeto y ámbito --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_2_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_2_scope') }}</p>
                <p>{{ __('ui.legal_terms_2_eu') }}</p>
            </div>

            {{-- 3. Descripción del servicio --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_3_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_3_desc') }}</p>
                <ul>
                    <li>{{ __('ui.legal_terms_3_upload') }}</li>
                    <li>{{ __('ui.legal_terms_3_styles') }}</li>
                    <li>{{ __('ui.legal_terms_3_generation') }}</li>
                    <li>{{ __('ui.legal_terms_3_preview') }}</li>
                    <li>{{ __('ui.legal_terms_3_download') }}</li>
                    <li>{{ __('ui.legal_terms_3_formats') }}</li>
                </ul>
                <p class="mt-3">{{ __('ui.legal_terms_3_ai_note') }}</p>
            </div>

            {{-- 4. Requisitos del usuario --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_4_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_4_age') }}</p>
                <p>{{ __('ui.legal_terms_4_minor') }}</p>
            </div>

            {{-- 5. Uso permitido --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_5_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_5_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_terms_5_own_photos') }}</li>
                    <li>{{ __('ui.legal_terms_5_personal') }}</li>
                    <li>{{ __('ui.legal_terms_5_gift') }}</li>
                    <li>{{ __('ui.legal_terms_5_social') }}</li>
                </ul>
                <p class="mt-3">{{ __('ui.legal_terms_5_responsibility') }}</p>
            </div>

            {{-- 6. Usos prohibidos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_6_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_6_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_terms_6_no_third') }}</li>
                    <li>{{ __('ui.legal_terms_6_no_illegal') }}</li>
                    <li>{{ __('ui.legal_terms_6_no_impersonate') }}</li>
                    <li>{{ __('ui.legal_terms_6_no_minor') }}</li>
                    <li>{{ __('ui.legal_terms_6_no_offensive') }}</li>
                    <li>{{ __('ui.legal_terms_6_no_commercial') }}</li>
                    <li>{{ __('ui.legal_terms_6_no_reverse') }}</li>
                    <li>{{ __('ui.legal_terms_6_no_bots') }}</li>
                </ul>
                <p class="mt-3">{{ __('ui.legal_terms_6_sole') }}</p>
            </div>

            {{-- 7. Fotos y contenido --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_7_title') }}</h2>
                <ul class="space-y-3">
                    <li><strong>{{ __('ui.legal_terms_7_yours') }}</strong> {{ __('ui.legal_terms_7_yours_text') }}</li>
                    <li><strong>{{ __('ui.legal_terms_7_license') }}</strong> {{ __('ui.legal_terms_7_license_text') }}</li>
                    <li><strong>{{ __('ui.legal_terms_7_generated') }}</strong> {{ __('ui.legal_terms_7_generated_text') }}</li>
                    <li><strong>{{ __('ui.legal_terms_7_deletion') }}</strong> {{ __('ui.legal_terms_7_deletion_text') }}</li>
                    <li><strong>{{ __('ui.legal_terms_7_refusal') }}</strong> {{ __('ui.legal_terms_7_refusal_text') }}</li>
                    <li><strong>{{ __('ui.legal_terms_7_gallery') }}</strong> {{ __('ui.legal_terms_7_gallery_text') }}</li>
                </ul>
            </div>

            {{-- 8. Precios y pagos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_8_title') }}</h2>
                <ul>
                    <li>{{ __('ui.legal_terms_8_preview') }}</li>
                    <li>{{ __('ui.legal_terms_8_prices') }}</li>
                    <li>{{ __('ui.legal_terms_8_stripe') }}</li>
                    <li>{{ __('ui.legal_terms_8_ttl') }}</li>
                    <li>{{ __('ui.legal_terms_8_invoice') }}</li>
                </ul>
            </div>

            {{-- 9. Derecho de desistimiento y reembolsos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_9_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_9_desist') }}</p>
                <p class="mb-3">{{ __('ui.legal_terms_9_refund') }}</p>
                <p>{{ __('ui.legal_terms_9_contact') }}</p>
            </div>

            {{-- 10. Propiedad intelectual --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_10_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_10_platform') }}</p>
                <p class="mb-3">{{ __('ui.legal_terms_10_reserved') }}</p>
                <p>{{ __('ui.legal_terms_10_user_content') }}</p>
            </div>

            {{-- 11. Protección de datos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_11_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_11_text') }}</p>
                <p>{{ __('ui.legal_terms_11_link') }}</p>
            </div>

            {{-- 12. Responsabilidad del usuario --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_12_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_12_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_terms_12_photos') }}</li>
                    <li>{{ __('ui.legal_terms_12_consent') }}</li>
                    <li>{{ __('ui.legal_terms_12_use') }}</li>
                    <li>{{ __('ui.legal_terms_12_verify') }}</li>
                    <li>{{ __('ui.legal_terms_12_comply') }}</li>
                </ul>
            </div>

            {{-- 13. Limitación de responsabilidad --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_13_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_13_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_terms_13_asis') }}</li>
                    <li>{{ __('ui.legal_terms_13_use') }}</li>
                    <li>{{ __('ui.legal_terms_13_ai_results') }}</li>
                    <li>{{ __('ui.legal_terms_13_ttl') }}</li>
                    <li>{{ __('ui.legal_terms_13_third') }}</li>
                </ul>
                <p class="mt-3">{{ __('ui.legal_terms_13_cap') }}</p>
                <p class="mt-3">{{ __('ui.legal_terms_13_eu') }}</p>
            </div>

            {{-- 14. Disponibilidad --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_14_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_14_text') }}</p>
                <p>{{ __('ui.legal_terms_14_failure') }}</p>
            </div>

            {{-- 15. Modificaciones --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_15_title') }}</h2>
                <p>{{ __('ui.legal_terms_15_text') }}</p>
            </div>

            {{-- 16. Resolución de disputas, ley y jurisdicción --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_terms_16_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_terms_16_amicable') }}</p>
                <p class="mb-3">{{ __('ui.legal_terms_16_odr') }}</p>
                <p class="mb-3">{{ __('ui.legal_terms_16_law') }}</p>
                <p>{{ __('ui.legal_terms_16_eu_courts') }}</p>
            </div>

            {{-- Contacto --}}
            <div class="bg-champagne rounded-xl p-6">
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">
                    {{ __('ui.legal_contact_title') }}
                </h2>
                <p>{{ __('ui.legal_terms_contact') }}</p>
                <p class="mt-2 font-semibold text-burgundy">{{ config('genpic.contact_email') }}</p>
            </div>

        </div>
    </div>
</section>
@endsection
