{{-- Hallmark · genre: editorial · family: contenido · macrostructure: Long Document · design-system: design.md · designed-as-app
     politica de cookies --}}
@extends('layouts.app')

@section('content')
<section class="bg-burgundy py-16 px-6">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="font-serif text-4xl font-bold text-white tracking-tight">
            {{ __('ui.legal_cookies_title') }}
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
            <p>{{ __('ui.legal_cookies_intro') }}</p>

            {{-- 1. Qué son --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_cookies_1_title') }}</h2>
                <p>{{ __('ui.legal_cookies_1_text') }}</p>
                <p class="mt-2">{{ __('ui.legal_cookies_1_minimal') }}</p>
            </div>

            {{-- 2. Clasificación --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_cookies_2_title') }}</h2>
                <p>{{ __('ui.legal_cookies_2_intro') }}</p>
            </div>

            {{-- 3. Cookies estrictamente necesarias --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_cookies_3_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_cookies_3_intro') }}</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-pmf-border rounded-lg overflow-hidden">
                        <thead class="bg-champagne">
                            <tr>
                                <th class="text-left p-3 font-semibold text-pmf-text">Cookie</th>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_cookies_provider') }}</th>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_purpose') }}</th>
                                <th class="text-left p-3 font-semibold text-pmf-text">{{ __('ui.legal_cookies_duration') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-pmf-border">
                            <tr>
                                <td class="p-3 font-mono text-xs">genpic_session</td>
                                <td class="p-3">GenPic</td>
                                <td class="p-3">{{ __('ui.legal_cookies_session_purpose') }}</td>
                                <td class="p-3">{{ __('ui.legal_cookies_session_duration') }}</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-xs">XSRF-TOKEN</td>
                                <td class="p-3">GenPic</td>
                                <td class="p-3">{{ __('ui.legal_cookies_csrf_purpose') }}</td>
                                <td class="p-3">{{ __('ui.legal_cookies_session_duration') }}</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-xs">cookieConsent</td>
                                <td class="p-3">GenPic</td>
                                <td class="p-3">{{ __('ui.legal_cookies_consent_purpose') }}</td>
                                <td class="p-3">12 {{ __('ui.legal_cookies_months') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. Cookies de terceros --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_cookies_4_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_cookies_4_stripe') }}</p>
                <p>{{ __('ui.legal_cookies_4_no_analytics') }}</p>
            </div>

            {{-- 5. Otras tecnologías --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_cookies_5_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_cookies_5_intro') }}</p>
                <ul class="space-y-2">
                    <li><strong>LocalStorage:</strong> {{ __('ui.legal_cookies_5_localstorage') }}</li>
                    <li><strong>{{ __('ui.legal_cookies_5_session_label') }}:</strong> {{ __('ui.legal_cookies_5_session_storage') }}</li>
                </ul>
            </div>

            {{-- 6. Gestión --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_cookies_6_title') }}</h2>
                <p class="mb-3">{{ __('ui.legal_cookies_6_text') }}</p>
                <p class="mb-3 font-semibold text-pmf-text">{{ __('ui.legal_cookies_6_browsers') }}</p>
                <ul>
                    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener" class="text-burgundy hover:underline">Google Chrome</a></li>
                    <li><a href="https://support.mozilla.org/kb/cookies-information-websites-store-on-your-computer" target="_blank" rel="noopener" class="text-burgundy hover:underline">Mozilla Firefox</a></li>
                    <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471" target="_blank" rel="noopener" class="text-burgundy hover:underline">Safari</a></li>
                    <li><a href="https://support.microsoft.com/microsoft-edge/manage-cookies-in-microsoft-edge" target="_blank" rel="noopener" class="text-burgundy hover:underline">Microsoft Edge</a></li>
                </ul>
            </div>

            {{-- 7. Cambios --}}
            <div>
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_cookies_7_title') }}</h2>
                <p>{{ __('ui.legal_cookies_7_text') }}</p>
            </div>

            {{-- Contacto --}}
            <div class="bg-champagne rounded-xl p-6">
                <h2 class="font-serif text-xl font-bold text-pmf-text mb-3">{{ __('ui.legal_contact_title') }}</h2>
                <p>{{ __('ui.legal_cookies_contact') }}</p>
                <p class="mt-2 font-semibold text-burgundy">{{ config('genpic.contact_email') }}</p>
            </div>

        </div>
    </div>
</section>
@endsection
