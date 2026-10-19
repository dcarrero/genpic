@extends('emails.layout')

@section('content')

    {{-- Success badge --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="background-color:#F0F9F4; border:1px solid #86C9A4; border-radius:24px; padding:10px 20px;">
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; font-weight:600; color:#1E7A47; letter-spacing:0.3px;">
                                &#10003;&nbsp;&nbsp;{{ __('ui.email_download_badge') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Heading --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
        <tr>
            <td>
                <h1 style="margin:0 0 12px 0; font-family:'Source Serif 4', Georgia, 'Times New Roman', serif; font-size:26px; font-weight:700; color:#3A1A21; line-height:1.25;">
                    {{ __('ui.email_download_title') }}
                </h1>
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:16px; color:#5A3D42; line-height:1.6;">
                    {{ __('ui.email_download_desc') }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Order reference --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr>
            <td style="background-color:#FDF8F4; border-radius:6px; padding:14px 18px; border:1px solid #EDE8E3;">
                <p style="margin:0 0 4px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:12px; font-weight:600; color:#9E8C88; text-transform:uppercase; letter-spacing:1px;">
                    {{ __('ui.email_download_order') }}
                </p>
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:15px; color:#3A1A21; font-weight:600;">
                    #{{ $order->id }} &mdash; {{ $orderedAt }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Download links --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:32px;">
        <tr>
            <td>
                <p style="margin:0 0 16px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; font-weight:600; color:#7A2D3B; text-transform:uppercase; letter-spacing:1px;">
                    {{ __('ui.email_download_section') }}
                </p>

                @foreach ($downloadLinks as $link)
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:12px;">
                    <tr>
                        <td style="background-color:#FFFFFF; border:1px solid #EDE8E3; border-radius:6px; padding:16px 18px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <p style="margin:0 0 2px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:15px; font-weight:600; color:#3A1A21;">
                                            {{ $link['label'] }}
                                        </p>
                                        @if ($link['description'])
                                        <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#9E8C88;">
                                            {{ $link['description'] }}
                                        </p>
                                        @endif
                                        <p style="margin:4px 0 0 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:12px; color:#BEB4B0;">
                                            {{ __('ui.email_download_valid', ['date' => $link['expires_at']]) }}
                                        </p>
                                    </td>
                                    <td style="vertical-align:middle; padding-left:16px; white-space:nowrap;">
                                        <a
                                            href="{{ $link['url'] }}"
                                            target="_blank"
                                            style="display:inline-block; padding:10px 20px; background-color:#7A2D3B; color:#FDF8F4; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; font-weight:600; text-decoration:none; border-radius:5px; white-space:nowrap;"
                                        >
                                            {{ __('ui.email_download_btn') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                @endforeach

            </td>
        </tr>
    </table>

    {{-- TTL notice --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
        <tr>
            <td style="background-color:#FFF8E7; border:1px solid #F5D98A; border-radius:6px; padding:14px 18px;">
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#7A6020; line-height:1.6;">
                    &#9888;&nbsp; {!! __('ui.email_download_ttl') !!}
                    <a href="mailto:{{ config('genpic.contact_email') }}" style="color:#7A2D3B; text-decoration:none;">{{ config('genpic.contact_email') }}</a>.
                </p>
            </td>
        </tr>
    </table>

    {{-- Closing message --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="border-top:1px solid #EDE8E3; padding-top:24px;">
                <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:14px; color:#9E8C88; line-height:1.7; text-align:center;">
                    {{ __('ui.email_download_closing') }}
                    <a href="{{ config('app.url') }}" style="color:#7A2D3B; text-decoration:none; font-weight:600;">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a>
                </p>
            </td>
        </tr>
    </table>

@endsection
