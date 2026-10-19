<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>{{ $subject ?? 'GenPic' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        /* Reset */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            display: block;
            outline: none;
            text-decoration: none;
        }
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            background-color: #FDF8F4;
        }
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            .email-content {
                padding: 28px 20px !important;
            }
            .btn-cta {
                width: 100% !important;
                display: block !important;
            }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#FDF8F4; font-family:'Barlow Semi Condensed', Arial, Helvetica, sans-serif;">

    <!-- Outer wrapper -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#FDF8F4;">
        <tr>
            <td align="center" style="padding: 32px 16px 48px 16px;">

                <!-- Email card -->
                <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="560" style="max-width:560px; width:100%;">

                    <!-- Header bar -->
                    <tr>
                        <td style="background-color:#7A2D3B; border-radius:8px 8px 0 0; padding:24px 40px; text-align:center;">
                            <a href="{{ config('app.url') }}" target="_blank" style="text-decoration:none;">
                                <span style="font-family:'Source Serif 4', Georgia, 'Times New Roman', serif; font-size:26px; font-weight:700; color:#FDF8F4; letter-spacing:-0.3px; text-decoration:none;">
                                    GenPic
                                </span>
                                <span style="display:block; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:11px; font-weight:400; color:#D4A0A8; letter-spacing:2px; text-transform:uppercase; margin-top:2px;">
                                    {{ __('ui.email_layout_subtitle') }}
                                </span>
                            </a>
                        </td>
                    </tr>

                    <!-- White content card -->
                    <tr>
                        <td class="email-content" style="background-color:#FFFFFF; border-radius:0 0 8px 8px; padding:40px 48px; border-left:1px solid #EDE8E3; border-right:1px solid #EDE8E3; border-bottom:1px solid #EDE8E3;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 0 0 0; text-align:center;">
                            <p style="margin:0 0 6px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#9E8C88; line-height:1.5;">
                                {{ __('ui.email_layout_footer_brand') }}
                            </p>
                            <p style="margin:0 0 6px 0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:13px; color:#9E8C88; line-height:1.5;">
                                <a href="mailto:{{ config('genpic.contact_email') }}" style="color:#7A2D3B; text-decoration:none;">{{ config('genpic.contact_email') }}</a>
                            </p>
                            <p style="margin:0; font-family:'Barlow Semi Condensed', Arial, sans-serif; font-size:12px; color:#BEB4B0; line-height:1.5;">
                                {{ __('ui.email_layout_footer_reason') }}
                                <a href="{{ config('app.url') }}" style="color:#BEB4B0; text-decoration:underline;">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a>.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Email card -->

            </td>
        </tr>
    </table>
    <!-- /Outer wrapper -->

</body>
</html>
