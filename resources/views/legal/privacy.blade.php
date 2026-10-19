{{-- Hallmark · genre: editorial · family: contenido · macrostructure: Long Document · design-system: design.md · designed-as-app
     politica de privacidad --}}
@extends('layouts.app')

@section('content')
<section class="bg-burgundy py-16 px-6">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="font-serif text-4xl font-bold text-white tracking-tight">
            {{ __('ui.legal_privacy_title') }}
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
            <p>{{ __('ui.legal_privacy_intro') }}</p>

            {{-- 1. Responsable --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_1_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_privacy_1_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_terms_1_name') }}</li>
                    <li>{{ __('ui.legal_terms_1_cif') }}</li>
                    <li>{{ __('ui.legal_terms_1_address') }}</li>
                    <li>{{ __('ui.legal_terms_1_email') }}</li>
                    <li>{{ __('ui.legal_terms_1_web') }}</li>
                </ul>
            </div>

            {{-- 2. Datos que recogemos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_2_title') }}</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-pmf-border rounded-lg overflow-hidden">
                        <thead class="bg-champagne">
                            <tr>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_data') }}</th>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_purpose') }}</th>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_retention') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-pmf-border">
                            <tr>
                                <td class="p-3">{{ __('ui.legal_email_data') }}</td>
                                <td class="p-3">{{ __('ui.legal_email_purpose') }}</td>
                                <td class="p-3">{{ __('ui.legal_email_retention') }}</td>
                            </tr>
                            <tr>
                                <td class="p-3">{{ __('ui.legal_photo_data') }}</td>
                                <td class="p-3">{{ __('ui.legal_photo_purpose') }}</td>
                                <td class="p-3 font-semibold">{{ __('ui.legal_photo_retention') }}</td>
                            </tr>
                            <tr>
                                <td class="p-3">{{ __('ui.legal_portrait_data') }}</td>
                                <td class="p-3">{{ __('ui.legal_portrait_purpose') }}</td>
                                <td class="p-3 font-semibold">{{ __('ui.legal_portrait_retention') }}</td>
                            </tr>
                            <tr>
                                <td class="p-3">{{ __('ui.legal_ip_data') }}</td>
                                <td class="p-3">{{ __('ui.legal_ip_purpose') }}</td>
                                <td class="p-3">{{ __('ui.legal_ip_retention') }}</td>
                            </tr>
                            <tr>
                                <td class="p-3">{{ __('ui.legal_payment_data') }}</td>
                                <td class="p-3">{{ __('ui.legal_payment_purpose') }}</td>
                                <td class="p-3">{{ __('ui.legal_payment_retention') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 3. Datos que NO recogemos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_3_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_privacy_3_intro') }}</p>
                <ul>
                    <li>{{ __('ui.legal_privacy_3_no_biometric') }}</li>
                    <li>{{ __('ui.legal_privacy_3_no_face') }}</li>
                    <li>{{ __('ui.legal_privacy_3_no_location') }}</li>
                    <li>{{ __('ui.legal_privacy_3_no_card') }}</li>
                    <li>{{ __('ui.legal_privacy_3_no_password') }}</li>
                </ul>
            </div>

            {{-- 4. Cómo procesamos tu foto --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_4_title') }}</h2>
                <ol class="list-decimal list-inside space-y-2">
                    <li>{{ __('ui.legal_process_1') }}</li>
                    <li>{{ __('ui.legal_process_2') }}</li>
                    <li>{{ __('ui.legal_process_3') }}</li>
                    <li>{{ __('ui.legal_process_4') }}</li>
                    <li>{{ __('ui.legal_process_5') }}</li>
                </ol>
            </div>

            {{-- 5. Base legal --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_5_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_privacy_5_intro') }}</p>
                <ul class="space-y-3">
                    <li><strong>{{ __('ui.legal_basis_consent') }}</strong> {{ __('ui.legal_basis_consent_text') }}</li>
                    <li><strong>{{ __('ui.legal_basis_contract') }}</strong> {{ __('ui.legal_basis_contract_text') }}</li>
                    <li><strong>{{ __('ui.legal_basis_interest') }}</strong> {{ __('ui.legal_basis_interest_text') }}</li>
                </ul>
            </div>

            {{-- 6. Encargados del tratamiento --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_6_title') }}</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-pmf-border rounded-lg overflow-hidden">
                        <thead class="bg-champagne">
                            <tr>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_service') }}</th>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_purpose') }}</th>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_location') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-pmf-border">
                            <tr><td class="p-3">{{ __('ui.legal_ai_providers') }}</td><td class="p-3">{{ __('ui.legal_ai_purpose') }}</td><td class="p-3">USA/EU</td></tr>
                            <tr><td class="p-3">Stripe</td><td class="p-3">{{ __('ui.legal_stripe_purpose') }}</td><td class="p-3">USA/EU</td></tr>
                            <tr><td class="p-3">Wasabi</td><td class="p-3">{{ __('ui.legal_wasabi_purpose') }}</td><td class="p-3">EU</td></tr>
                            <tr><td class="p-3">Stackscale</td><td class="p-3">{{ __('ui.legal_stackscale_purpose') }}</td><td class="p-3">EU</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-sm">{{ __('ui.legal_processors_note') }}</p>
            </div>

            {{-- 7. Transferencias internacionales --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_7_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_privacy_7_text') }}</p>
                <ul>
                    <li>{{ __('ui.legal_privacy_7_sccs') }}</li>
                    <li>{{ __('ui.legal_privacy_7_tia') }}</li>
                </ul>
                <p class="mt-3">{{ __('ui.legal_privacy_7_note') }}</p>
            </div>

            {{-- 8. Conservación de datos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_8_title') }}</h2>
                <ul>
                    <li>{{ __('ui.legal_privacy_8_photos') }}</li>
                    <li>{{ __('ui.legal_privacy_8_portraits') }}</li>
                    <li>{{ __('ui.legal_privacy_8_email') }}</li>
                    <li>{{ __('ui.legal_privacy_8_ip') }}</li>
                    <li>{{ __('ui.legal_privacy_8_cookies') }}</li>
                    <li>{{ __('ui.legal_privacy_8_analytics') }}</li>
                </ul>
            </div>

            {{-- 9. Derechos --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_9_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_rights_intro') }}</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>{{ __('ui.legal_right_access') }}</li>
                    <li>{{ __('ui.legal_right_rectify') }}</li>
                    <li>{{ __('ui.legal_right_delete') }}</li>
                    <li>{{ __('ui.legal_right_restrict') }}</li>
                    <li>{{ __('ui.legal_right_portability') }}</li>
                    <li>{{ __('ui.legal_right_withdraw') }}</li>
                </ul>
                <p class="mt-3">{{ __('ui.legal_rights_contact') }}</p>
            </div>

            {{-- 10. Seguridad --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_10_title') }}</h2>
                <ul class="list-disc list-inside space-y-1">
                    <li>{{ __('ui.legal_security_https') }}</li>
                    <li>{{ __('ui.legal_security_private') }}</li>
                    <li>{{ __('ui.legal_security_signed') }}</li>
                    <li>{{ __('ui.legal_security_rate') }}</li>
                    <li>{{ __('ui.legal_security_purge') }}</li>
                </ul>
            </div>

            {{-- 11. Menores --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_11_title') }}</h2>
                <p>{{ __('ui.legal_children_text') }}</p>
            </div>

            {{-- 12. Modificaciones --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_privacy_12_title') }}</h2>
                <p>{{ __('ui.legal_privacy_12_text') }}</p>
            </div>

            {{-- Contacto --}}
            <div class="bg-champagne rounded-xl p-6">
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_contact_title') }}</h2>
                <p>{{ __('ui.legal_privacy_contact') }}</p>
                <p class="mt-2 font-semibold text-burgundy">{{ config('genpic.contact_email') }}</p>
            </div>

        </div>
    </div>
</section>
@endsection
