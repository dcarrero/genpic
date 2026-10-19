@extends('emails.layout')

@section('content')

    {{-- Preview image placeholder --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:32px;">
        <tr>
            <td align="center">
                @if ($session->watermarked_url ?? false)
                    <img
                        src="{{ $session->watermarked_url }}"
                        alt="{{ __('ui.email_session_alt') }}"
                        width="464"
                        style="width:100%; max-width:464px; border-radius:6px; display:block; border:3px solid #EDE8E3;"
                    />
                @else
                    {{-- Placeholder cuando la imagen aún no está disponible --}}
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="464" style="max-width:464px; width:100%; background-color:#F5EFE9; border-radius:6px; border:2px dashed #D4BFB8;">
                        <tr>
                            <td align="center" style="padding:48px 24px;">
                                <p style="margin:0 0 8px 0; font-size:40px; line-height:1;">&#127912;</p>
                                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:15px; color:#9E8C88;">
                                    {{ __('ui.email_session_alt') }}
                                </p>
                            </td>
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    {{-- Heading --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr>
            <td>
                <h1 style="margin:0 0 12px 0; font-family:'Source Serif 4', Georgia, 'Times New Roman', serif; font-size:26px; font-weight:700; color:#3A1A21; line-height:1.25;">
                    {{ __('ui.email_session_title') }}
                </h1>
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:16px; color:#5A3D42; line-height:1.6;">
                    {{ __('ui.email_session_desc') }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Price callout --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#FDF8F4; border-left:3px solid #7A2D3B; border-radius:0 4px 4px 0; padding:14px 18px;">
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:15px; color:#5A3D42; line-height:1.5;">
                    @if(config('genpic.free_web_basic_enabled'))
                        {!! __('ui.free_promo_price_note') !!}
                    @else
                        {!! __('ui.email_session_price') !!}
                    @endif
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
                                href="{{ $accessUrl }}"
                                target="_blank"
                                class="btn-cta"
                                style="display:inline-block; padding:16px 36px; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:17px; font-weight:600; color:#FDF8F4; text-decoration:none; border-radius:6px; letter-spacing:0.2px; white-space:nowrap;"
                            >
                                {{ __('ui.email_session_cta') }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Expiry notice --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="border-top:1px solid #EDE8E3; padding-top:24px;">
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#9E8C88; line-height:1.6; text-align:center;">
                    {!! __('ui.email_session_expiry', ['date' => $expiresAt]) !!}
                </p>
            </td>
        </tr>
    </table>

@endsection
