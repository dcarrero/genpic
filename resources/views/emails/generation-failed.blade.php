@extends('emails.layout')

@section('content')

    {{-- Apology icon --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
        <tr>
            <td align="center">
                <div style="display:inline-block; width:64px; height:64px; background-color:#FDF0F2; border-radius:50%; text-align:center; line-height:64px; font-size:28px;">
                    &#128543;
                </div>
            </td>
        </tr>
    </table>

    {{-- Heading --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr>
            <td align="center">
                <h1 style="margin:0 0 12px 0; font-family:'Source Serif 4', Georgia, 'Times New Roman', serif; font-size:24px; font-weight:700; color:#3A1A21; line-height:1.3; text-align:center;">
                    {{ __('ui.email_failed_title') }}
                </h1>
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:16px; color:#5A3D42; line-height:1.7; text-align:center;">
                    {{ __('ui.email_failed_desc') }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Explanation box --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#FDF8F4; border-left:3px solid #7A2D3B; border-radius:0 4px 4px 0; padding:16px 20px;">
                <p style="margin:0 0 8px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:15px; font-weight:600; color:#3A1A21;">
                    {{ __('ui.email_failed_what') }}
                </p>
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#5A3D42; line-height:1.7;">
                    {{ __('ui.email_failed_explanation') }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Free retry offer --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#F0F9F4; border:1px solid #86C9A4; border-radius:6px; padding:20px 24px; text-align:center;">
                <p style="margin:0 0 6px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:18px; font-weight:700; color:#1E7A47;">
                    {{ __('ui.email_failed_retry_title') }}
                </p>
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#2D6B4A; line-height:1.6;">
                    {!! __('ui.email_failed_retry_desc') !!}
                </p>
            </td>
        </tr>
    </table>

    {{-- CTA Button --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:32px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="border-radius:6px; background-color:#7A2D3B;">
                            <a
                                href="{{ $newGenerationUrl }}"
                                target="_blank"
                                class="btn-cta"
                                style="display:inline-block; padding:16px 36px; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:17px; font-weight:600; color:#FDF8F4; text-decoration:none; border-radius:6px; letter-spacing:0.2px; white-space:nowrap;"
                            >
                                {{ __('ui.email_failed_cta') }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Tips --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
        <tr>
            <td>
                <p style="margin:0 0 12px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; font-weight:600; color:#7A2D3B; text-transform:uppercase; letter-spacing:1px;">
                    {{ __('ui.email_failed_tips_title') }}
                </p>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding:6px 0; vertical-align:top; width:20px;">
                            <span style="color:#7A2D3B; font-size:16px;">&#8226;</span>
                        </td>
                        <td style="padding:6px 0; padding-left:8px;">
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#5A3D42; line-height:1.6;">
                                {{ __('ui.email_failed_tip1') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; vertical-align:top; width:20px;">
                            <span style="color:#7A2D3B; font-size:16px;">&#8226;</span>
                        </td>
                        <td style="padding:6px 0; padding-left:8px;">
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#5A3D42; line-height:1.6;">
                                {{ __('ui.email_failed_tip2') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; vertical-align:top; width:20px;">
                            <span style="color:#7A2D3B; font-size:16px;">&#8226;</span>
                        </td>
                        <td style="padding:6px 0; padding-left:8px;">
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#5A3D42; line-height:1.6;">
                                {{ __('ui.email_failed_tip3') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Footer note --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="border-top:1px solid #EDE8E3; padding-top:20px; text-align:center;">
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#9E8C88; line-height:1.6;">
                    {{ __('ui.email_failed_footer') }}
                    <a href="mailto:{{ config('genpic.contact_email') }}" style="color:#7A2D3B; text-decoration:none;">{{ config('genpic.contact_email') }}</a>
                    {{ __('ui.email_failed_footer2') }}
                </p>
            </td>
        </tr>
    </table>

@endsection
