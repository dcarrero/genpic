@extends('emails.layout')

@section('content')

    {{-- Urgency badge --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="background-color:#FFF3F5; border:1px solid #D4A0A8; border-radius:24px; padding:10px 20px;">
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; font-weight:600; color:#7A2D3B; letter-spacing:0.3px;">
                                &#9203;&nbsp;&nbsp;
                                {{ trans_choice('ui.email_expiring_badge', $daysLeft, ['days' => $daysLeft]) }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Heading --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr>
            <td>
                <h1 style="margin:0 0 12px 0; font-family:'Source Serif 4', Georgia, 'Times New Roman', serif; font-size:26px; font-weight:700; color:#3A1A21; line-height:1.25;">
                    {{ __('ui.email_expiring_title') }}
                </h1>
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:16px; color:#5A3D42; line-height:1.7;">
                    {!! __('ui.email_expiring_desc', ['date' => $expiresAt]) !!}
                </p>
            </td>
        </tr>
    </table>

    {{-- Preview reminder block --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#FDF8F4; border-radius:8px; padding:20px; border:1px solid #EDE8E3; text-align:center;">
                @if ($session->watermarked_url ?? false)
                    <img
                        src="{{ $session->watermarked_url }}"
                        alt="{{ __('ui.email_expiring_alt') }}"
                        width="320"
                        style="max-width:320px; width:100%; border-radius:4px; display:block; margin:0 auto; opacity:0.85;"
                    />
                @else
                    <p style="margin:0; font-size:40px; line-height:1;">&#127912;</p>
                    <p style="margin:8px 0 0 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#9E8C88;">
                        {{ __('ui.email_expiring_alt') }}
                    </p>
                @endif
            </td>
        </tr>
    </table>

    {{-- Price reminder --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#FDF8F4; border-left:3px solid #7A2D3B; border-radius:0 4px 4px 0; padding:14px 18px;">
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:15px; color:#5A3D42; line-height:1.6;">
                    {!! __('ui.email_expiring_price') !!}
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
                                {{ __('ui.email_expiring_cta') }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- What you'll get --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
        <tr>
            <td>
                <p style="margin:0 0 12px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; font-weight:600; color:#7A2D3B; text-transform:uppercase; letter-spacing:1px;">
                    {{ __('ui.email_expiring_benefits_title') }}
                </p>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding:5px 0; vertical-align:top; width:20px;">
                            <span style="color:#7A2D3B; font-size:14px;">&#10003;</span>
                        </td>
                        <td style="padding:5px 0; padding-left:8px;">
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#5A3D42;">
                                {{ __('ui.email_expiring_benefit1') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0; vertical-align:top; width:20px;">
                            <span style="color:#7A2D3B; font-size:14px;">&#10003;</span>
                        </td>
                        <td style="padding:5px 0; padding-left:8px;">
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#5A3D42;">
                                {{ __('ui.email_expiring_benefit2') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0; vertical-align:top; width:20px;">
                            <span style="color:#7A2D3B; font-size:14px;">&#10003;</span>
                        </td>
                        <td style="padding:5px 0; padding-left:8px;">
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#5A3D42;">
                                {{ __('ui.email_expiring_benefit3') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Expiry warning --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="border-top:1px solid #EDE8E3; padding-top:20px; text-align:center;">
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#9E8C88; line-height:1.6;">
                    {!! __('ui.email_expiring_footer', ['date' => $expiresAt]) !!}
                </p>
            </td>
        </tr>
    </table>

    {{-- Unsubscribe --}}
    @if($session->user)
    <p style="margin-top: 30px; font-size: 11px; color: #999; text-align: center;">
        <a href="{{ URL::signedRoute('unsubscribe', ['email' => $session->user->email]) }}" style="color: #999;">
            {{ __('ui.unsubscribe_link_text', [], $session->locale ?? 'es') }}
        </a>
    </p>
    @endif

@endsection
