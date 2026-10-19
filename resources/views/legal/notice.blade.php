{{-- Hallmark · genre: editorial · family: contenido · macrostructure: Long Document · design-system: design.md · designed-as-app
     aviso legal --}}
@extends('layouts.app')

@section('content')
<section class="bg-burgundy py-16 px-6">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="font-serif text-4xl font-bold text-white tracking-tight">
            {{ __('ui.legal_notice_title') }}
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
            <p>{{ __('ui.legal_notice_intro') }}</p>

            {{-- 1. Identificación --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_1_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_notice_1_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_terms_1_name') }}</li>
                    <li>{{ __('ui.legal_terms_1_cif') }}</li>
                    <li>{{ __('ui.legal_terms_1_address') }}</li>
                    <li>{{ __('ui.legal_terms_1_email') }}</li>
                    <li>{{ __('ui.legal_terms_1_web') }}</li>
                </ul>
            </div>

            {{-- 2. Objeto --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_2_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_notice_2_text') }}</p>
                <ul>
                    <li>{{ __('ui.legal_notice_2_upload') }}</li>
                    <li>{{ __('ui.legal_notice_2_styles') }}</li>
                    <li>{{ __('ui.legal_notice_2_preview') }}</li>
                    <li>{{ __('ui.legal_notice_2_download') }}</li>
                </ul>
            </div>

            {{-- 3. Condiciones de acceso --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_3_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_notice_3_text') }}</p>
                <ul>
                    <li>{{ __('ui.legal_notice_3_lawful') }}</li>
                    <li>{{ __('ui.legal_notice_3_no_fraud') }}</li>
                    <li>{{ __('ui.legal_notice_3_own_photos') }}</li>
                    <li>{{ __('ui.legal_notice_3_ip') }}</li>
                </ul>
                <p class="mt-3">{{ __('ui.legal_notice_3_link') }}</p>
            </div>

            {{-- 4. Propiedad intelectual --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_4_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_notice_4_text') }}</p>
                <p class="mb-3">{{ __('ui.legal_notice_4_allowed') }}</p>
                <p>{{ __('ui.legal_notice_4_user_content') }}</p>
            </div>

            {{-- 5. Exclusión de garantías --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_5_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_notice_5_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_notice_5_use') }}</li>
                    <li>{{ __('ui.legal_notice_5_ai') }}</li>
                    <li>{{ __('ui.legal_notice_5_interruptions') }}</li>
                    <li>{{ __('ui.legal_notice_5_viruses') }}</li>
                    <li>{{ __('ui.legal_notice_5_accuracy') }}</li>
                </ul>
                <p class="mt-3 text-sm italic">{{ __('ui.legal_notice_5_eu_note') }}</p>
            </div>

            {{-- 6. Protección de datos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_6_title') }}</h2>
                <p>{{ __('ui.legal_notice_6_text') }}</p>
            </div>

            {{-- 7. Enlaces a terceros --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_7_title') }}</h2>
                <p>{{ __('ui.legal_notice_7_text') }}</p>
            </div>

            {{-- 8. Accesibilidad --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_8_title') }}</h2>
                <p>{{ __('ui.legal_notice_8_text') }}</p>
            </div>

            {{-- 9. Modificaciones --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_9_title') }}</h2>
                <p>{{ __('ui.legal_notice_9_text') }}</p>
            </div>

            {{-- 10. Legislación --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_notice_10_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_notice_10_law') }}</p>
                <p>{{ __('ui.legal_notice_10_eu') }}</p>
            </div>

            {{-- Contacto --}}
            <div class="bg-champagne rounded-xl p-6">
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_contact_title') }}</h2>
                <p>{{ __('ui.legal_notice_contact') }}</p>
                <p class="mt-2 font-semibold text-burgundy">{{ config('genpic.contact_email') }}</p>
            </div>

        </div>
    </div>
</section>
@endsection
