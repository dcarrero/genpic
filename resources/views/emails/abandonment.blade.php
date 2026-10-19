@extends('emails.layout')

@section('content')

    {{-- Heading + body (contenido editable desde admin) --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr>
            <td>
                <h1 style="margin:0 0 12px 0; font-family:'Source Serif 4', Georgia, 'Times New Roman', serif; font-size:26px; font-weight:700; color:#3A1A21; line-height:1.25;">
                    {{ $emailHeading }}
                </h1>
                <div style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:16px; color:#5A3D42; line-height:1.6;">
                    {!! $emailBody !!}
                </div>
            </td>
        </tr>
    </table>

    {{-- Coupon block --}}
    @if($couponCode && $discountPercent > 0)
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#F0FDF4; border:2px dashed #22C55E; border-radius:8px; padding:20px; text-align:center;">
                <p style="margin:0 0 12px 0; font-family:'Source Serif 4', Georgia, serif; font-size:32px; font-weight:700; color:#166534;">
                    {{ $discountPercent }}%
                </p>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                    <tr>
                        <td style="background-color:#DCFCE7; border:1px solid #BBF7D0; border-radius:4px; padding:8px 20px;">
                            <p style="margin:0; font-family:monospace; font-size:20px; font-weight:700; color:#166534; letter-spacing:2px;">
                                {{ $couponCode }}
                            </p>
                        </td>
                    </tr>
                </table>
                @if($emailCouponText)
                <p style="margin:12px 0 0 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#5A3D42;">
                    {{ $emailCouponText }}
                </p>
                @endif
            </td>
        </tr>
    </table>
    @endif

    {{-- CTA Button --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:32px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="border-radius:6px; background-color:#7A2D3B;">
                            <a href="{{ $couponCode ? $checkoutUrl . '?coupon=' . $couponCode : $previewUrl }}"
                               target="_blank"
                               style="display:inline-block; padding:16px 36px; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:17px; font-weight:600; color:#FDF8F4; text-decoration:none; border-radius:6px;">
                                {{ $emailCta }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Expiry --}}
    @if($daysLeft > 0)
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="border-top:1px solid #EDE8E3; padding-top:24px;">
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#9E8C88; line-height:1.6; text-align:center;">
                    {{ __('ui.email_abandonment_expiry', ['days' => $daysLeft]) }}
                </p>
            </td>
        </tr>
    </table>
    @endif

    {{-- Unsubscribe --}}
    @if($session->user)
    <p style="margin-top: 30px; font-size: 11px; color: #999; text-align: center;">
        <a href="{{ URL::signedRoute('unsubscribe', ['email' => $session->user->email]) }}" style="color: #999;">
            {{ __('ui.unsubscribe_link_text', [], $session->locale ?? 'es') }}
        </a>
    </p>
    @endif

@endsection
