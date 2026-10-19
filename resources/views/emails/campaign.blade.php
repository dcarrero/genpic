<!DOCTYPE html>
<html lang="{{ $locale }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style type="text/css">
        body { margin:0; padding:0; width:100%!important; background-color:#FDF8F4; font-family:'Barlow Semi Condensed',Arial,sans-serif; }
        table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { border:0; display:block; outline:none; }
        a { color:#7A2D3B; }
        .btn-cta:hover { opacity:0.9; }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#FDF8F4;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#FDF8F4;">
<tr><td align="center" style="padding:24px 16px;">

    {{-- Container --}}
    <table role="presentation" width="560" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;width:100%;background:#FFFFFF;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);">

        {{-- Header con logos GenPic + face.co --}}
        <tr>
            <td style="background-color:#7A2D3B;padding:16px 32px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="text-align:left;vertical-align:middle;">
                            <a href="{{ config('app.url') }}" target="_blank" style="text-decoration:none;">
                                <span style="font-family:'Source Serif 4',Georgia,serif;font-size:26px;font-weight:700;color:#FDF8F4;letter-spacing:0.5px;">GenPic</span>
                            </a>
                        </td>
                        <td style="text-align:right;vertical-align:middle;">
                            <a href="https://face.co" target="_blank" style="text-decoration:none;">
                                <img src="https://face.co/images/logo.png" alt="face.co" width="80" height="auto" style="display:inline-block;max-width:80px;height:auto;" />
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Body content --}}
        <tr>
            <td style="padding:32px;">
                @if($userName)
                <p style="margin:0 0 16px 0;font-size:16px;color:#5A3D42;line-height:1.6;">
                    {{ match($locale) {
                        'es' => "Hola {$userName},",
                        'fr' => "Bonjour {$userName},",
                        'pt' => "Olá {$userName},",
                        'it' => "Ciao {$userName},",
                        default => "Hi {$userName},",
                    } }}
                </p>
                @endif

                <div style="font-size:15px;color:#3A1A21;line-height:1.7;">
                    {!! $body !!}
                </div>

                {{-- CTA Button --}}
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:28px 0;">
                    <tr>
                        <td align="center">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="border-radius:6px;background-color:#7A2D3B;">
                                        <a href="{{ $ctaUrl }}" target="_blank" class="btn-cta"
                                           style="display:inline-block;padding:16px 36px;font-size:17px;font-weight:600;color:#FDF8F4;text-decoration:none;border-radius:6px;letter-spacing:0.2px;">
                                            {{ match($locale) {
                                                'es' => 'Crear mi retrato gratis →',
                                                'fr' => 'Créer mon portrait gratuit →',
                                                'pt' => 'Criar meu retrato grátis →',
                                                'it' => 'Crea il mio ritratto gratis →',
                                                default => 'Create my free portrait →',
                                            } }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                {{-- Coupon --}}
                @if($couponCode)
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
                    <tr>
                        <td style="background-color:#FDF8F4;border-left:3px solid #7A2D3B;border-radius:0 4px 4px 0;padding:14px 18px;">
                            <p style="margin:0;font-size:14px;color:#5A3D42;line-height:1.5;">
                                {{ match($locale) {
                                    'es' => 'Usa el cupón',
                                    'fr' => 'Utilisez le coupon',
                                    'pt' => 'Use o cupom',
                                    'it' => 'Usa il coupon',
                                    default => 'Use coupon',
                                } }}
                                <strong style="color:#7A2D3B;font-size:18px;letter-spacing:1px;"> {{ $couponCode }}</strong>
                                {{ match($locale) {
                                    'es' => 'para obtener tu descuento exclusivo.',
                                    'fr' => 'pour obtenir votre réduction exclusive.',
                                    'pt' => 'para obter o seu desconto exclusivo.',
                                    'it' => 'per ottenere il tuo sconto esclusivo.',
                                    default => 'to get your exclusive discount.',
                                } }}
                            </p>
                        </td>
                    </tr>
                </table>
                @endif
            </td>
        </tr>

        {{-- face.co mention --}}
        <tr>
            <td style="padding:0 32px 24px 32px;">
                <p style="margin:0;font-size:12px;color:#A89098;line-height:1.6;text-align:center;">
                    {{ match($locale) {
                        'es' => 'Recibes este email como suscriptor de face.co, un proyecto hermano de GenPic.',
                        'fr' => 'Vous recevez cet email en tant qu\'abonné de face.co, un projet frère de GenPic.',
                        'pt' => 'Recebe este email como subscritor do face.co, um projeto irmão do GenPic.',
                        'it' => 'Ricevi questa email come iscritto a face.co, un progetto fratello di GenPic.',
                        default => 'You\'re receiving this email as a face.co subscriber, a sister project of GenPic.',
                    } }}
                </p>
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="background-color:#F0E8E0;padding:20px 32px;text-align:center;">
                <p style="margin:0 0 8px 0;font-size:12px;color:#8C6B73;">
                    {{ config('genpic.company.name') }}
                </p>
                <p style="margin:0;font-size:11px;color:#A89098;">
                    <a href="{{ $unsubscribeUrl }}" style="color:#A89098;text-decoration:underline;">
                        {{ match($locale) {
                            'es' => 'Cancelar suscripción',
                            'fr' => 'Se désabonner',
                            'pt' => 'Cancelar subscrição',
                            'it' => 'Annulla iscrizione',
                            default => 'Unsubscribe',
                        } }}
                    </a>
                </p>
            </td>
        </tr>

    </table>

</td></tr>
</table>
</body>
</html>
