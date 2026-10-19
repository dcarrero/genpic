@extends('emails.layout')

@php $locale = $session->locale ?? 'es'; @endphp

@section('content')

    {{-- Heading --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr><td>
            <h1 style="margin:0 0 12px 0;font-family:'Source Serif 4',Georgia,serif;font-size:26px;font-weight:700;color:#3A1A21;line-height:1.25;">
                {{ match($locale) {
                    'en' => 'Your free portrait is ready!',
                    'fr' => 'Votre portrait gratuit est prêt !',
                    'pt' => 'O seu retrato grátis está pronto!',
                    'it' => 'Il tuo ritratto gratis è pronto!',
                    default => '¡Tu retrato gratis está listo!',
                } }}
            </h1>
            <p style="margin:0;font-family:'Barlow Semi Condensed',Arial,sans-serif;font-size:16px;color:#5A3D42;line-height:1.6;">
                {{ match($locale) {
                    'en' => 'Your AI portrait in Web Basic quality is ready to download. Click the button below.',
                    'fr' => 'Votre portrait IA en qualité Web Basic est prêt à télécharger. Cliquez ci-dessous.',
                    'pt' => 'O seu retrato IA em qualidade Web Basic está pronto para descarregar. Clique abaixo.',
                    'it' => 'Il tuo ritratto IA in qualità Web Basic è pronto per il download. Clicca qui sotto.',
                    default => 'Tu retrato IA en calidad Web Basic está listo para descargar. Haz clic en el botón.',
                } }}
            </p>
        </td></tr>
    </table>

    {{-- Download CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr><td align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr><td style="border-radius:6px;background-color:#7A2D3B;">
                    <a href="{{ $downloadUrl }}" target="_blank" style="display:inline-block;padding:16px 36px;font-family:'Barlow Semi Condensed',Arial,sans-serif;font-size:17px;font-weight:600;color:#FDF8F4;text-decoration:none;border-radius:6px;">
                        {{ match($locale) {
                            'en' => 'Download my free portrait →',
                            'fr' => 'Télécharger mon portrait gratuit →',
                            'pt' => 'Descarregar meu retrato grátis →',
                            'it' => 'Scarica il mio ritratto gratis →',
                            default => 'Descargar mi retrato gratis →',
                        } }}
                    </a>
                </td></tr>
            </table>
        </td></tr>
    </table>

    {{-- Upsell separator --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
        <tr><td style="border-top:2px solid #EDE8E3;padding-top:24px;">
            <h2 style="margin:0 0 12px 0;font-family:'Source Serif 4',Georgia,serif;font-size:22px;font-weight:700;color:#7A2D3B;line-height:1.3;">
                {{ match($locale) {
                    'en' => 'Want the full experience?',
                    'fr' => 'Envie de l\'expérience complète ?',
                    'pt' => 'Quer a experiência completa?',
                    'it' => 'Vuoi l\'esperienza completa?',
                    default => '¿Quieres la experiencia completa?',
                } }}
            </h2>
            <p style="margin:0 0 16px 0;font-family:'Barlow Semi Condensed',Arial,sans-serif;font-size:15px;color:#5A3D42;line-height:1.6;">
                {{ match($locale) {
                    'en' => 'Get the Complete Pack: all formats, wallpapers for mobile and desktop, HD quality and print-ready resolution. Without watermark or courtesy text.',
                    'fr' => 'Obtenez le Pack Complet : tous les formats, fonds d\'écran mobile et bureau, qualité HD et résolution impression. Sans filigrane ni texte de courtoisie.',
                    'pt' => 'Obtenha o Pack Completo: todos os formatos, wallpapers para móvel e desktop, qualidade HD e resolução para impressão. Sem marca de água nem texto de cortesia.',
                    'it' => 'Ottieni il Pack Completo: tutti i formati, sfondi per mobile e desktop, qualità HD e risoluzione stampa. Senza filigrana o testo di cortesia.',
                    default => 'Consigue el Pack Completo: todos los formatos, fondos de pantalla para móvil y escritorio, calidad HD y resolución de impresión. Sin marca de agua ni texto de cortesía.',
                } }}
            </p>
        </td></tr>
    </table>

    {{-- Coupon callout --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
        <tr><td style="background-color:#FDF8F4;border-left:3px solid #7A2D3B;border-radius:0 4px 4px 0;padding:14px 18px;">
            <p style="margin:0;font-family:'Barlow Semi Condensed',Arial,sans-serif;font-size:15px;color:#5A3D42;line-height:1.5;">
                {{ match($locale) {
                    'en' => 'Use coupon',
                    'fr' => 'Utilisez le coupon',
                    'pt' => 'Use o cupão',
                    'it' => 'Usa il coupon',
                    default => 'Usa el cupón',
                } }}
                <strong style="color:#7A2D3B;font-size:20px;letter-spacing:1px;"> {{ $couponCode }}</strong>
                {{ match($locale) {
                    'en' => 'and get 50% off the Complete Pack — from €9.99 to',
                    'fr' => 'et obtenez -50% sur le Pack Complet — de 9,99€ à',
                    'pt' => 'e obtenha 50% de desconto no Pack Completo — de 9,99€ a',
                    'it' => 'e ottieni il 50% di sconto sul Pack Completo — da 9,99€ a',
                    default => 'y obtén un 50% en el Pack Completo — de 9,99€ a',
                } }}
                <strong style="color:#7A2D3B;font-size:18px;">4,99€</strong>
            </p>
        </td></tr>
    </table>

    {{-- Upsell CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:28px;">
        <tr><td align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr><td style="border-radius:6px;background-color:#D4956A;">
                    <a href="{{ $upsellUrl }}" target="_blank" style="display:inline-block;padding:14px 32px;font-family:'Barlow Semi Condensed',Arial,sans-serif;font-size:16px;font-weight:600;color:#FFFFFF;text-decoration:none;border-radius:6px;">
                        {{ match($locale) {
                            'en' => 'Get Complete Pack at 50% off →',
                            'fr' => 'Obtenir le Pack Complet à -50% →',
                            'pt' => 'Obter Pack Completo com 50% →',
                            'it' => 'Ottieni Pack Completo al 50% →',
                            default => 'Conseguir Pack Completo al 50% →',
                        } }}
                    </a>
                </td></tr>
            </table>
        </td></tr>
    </table>

    {{-- Expiry --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr><td style="border-top:1px solid #EDE8E3;padding-top:20px;">
            <p style="margin:0;font-family:'Barlow Semi Condensed',Arial,sans-serif;font-size:13px;color:#9E8C88;line-height:1.6;text-align:center;">
                {{ match($locale) {
                    'en' => 'The download link is valid for 14 days.',
                    'fr' => 'Le lien de téléchargement est valable 14 jours.',
                    'pt' => 'O link de download é válido por 14 dias.',
                    'it' => 'Il link di download è valido per 14 giorni.',
                    default => 'El enlace de descarga es válido durante 14 días.',
                } }}
            </p>
        </td></tr>
    </table>

@endsection
