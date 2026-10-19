{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     descarga gratuita --}}
@extends('layouts.app')

@section('content')
<section class="py-16 px-6">
    <div class="max-w-lg mx-auto text-center space-y-8">

        {{-- Descarga --}}
        <div class="space-y-4">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto text-3xl">✓</div>
            <h1 class="font-serif text-3xl font-bold text-pmf-text">
                {{ match($locale) {
                    'en' => 'Your free portrait is ready!',
                    'fr' => 'Votre portrait gratuit est prêt !',
                    'pt' => 'O seu retrato grátis está pronto!',
                    'it' => 'Il tuo ritratto gratis è pronto!',
                    default => '¡Tu retrato gratis está listo!',
                } }}
            </h1>
            <p class="text-pmf-secondary">
                {{ match($locale) {
                    'en' => 'Click the button below to download your Web Basic portrait.',
                    'fr' => 'Cliquez ci-dessous pour télécharger votre portrait Web Basic.',
                    'pt' => 'Clique abaixo para descarregar o seu retrato Web Basic.',
                    'it' => 'Clicca qui sotto per scaricare il tuo ritratto Web Basic.',
                    default => 'Pulsa el botón para descargar tu retrato Web Basic.',
                } }}
            </p>
            <a href="{{ $directDownloadUrl }}" class="inline-block bg-green-600 text-white font-bold text-base px-8 py-4 rounded-lg hover:bg-green-700 transition tracking-wide">
                {{ match($locale) {
                    'en' => 'Download my free portrait →',
                    'fr' => 'Télécharger mon portrait gratuit →',
                    'pt' => 'Descarregar meu retrato grátis →',
                    'it' => 'Scarica il mio ritratto gratis →',
                    default => 'Descargar mi retrato gratis →',
                } }}
            </a>
            <p class="text-xs text-pmf-muted">
                {{ match($locale) {
                    'en' => 'Web Basic quality (1024px) with courtesy watermark.',
                    'fr' => 'Qualité Web Basic (1024px) avec filigrane de courtoisie.',
                    'pt' => 'Qualidade Web Basic (1024px) com marca de cortesia.',
                    'it' => 'Qualità Web Basic (1024px) con filigrana di cortesia.',
                    default => 'Calidad Web Basic (1024px) con marca de cortesía.',
                } }}
            </p>
        </div>

        {{-- Separador --}}
        <div class="border-t-2 border-pmf-border pt-8">
            <h2 class="font-serif text-2xl font-bold text-burgundy mb-3">
                {{ match($locale) {
                    'en' => 'Want the full experience?',
                    'fr' => 'Envie de l\'expérience complète ?',
                    'pt' => 'Quer a experiência completa?',
                    'it' => 'Vuoi l\'esperienza completa?',
                    default => '¿Quieres la experiencia completa?',
                } }}
            </h2>
            <p class="text-pmf-secondary text-sm leading-relaxed mb-4">
                {{ match($locale) {
                    'en' => 'Get the Complete Pack: all formats, mobile and desktop wallpapers, HD quality and print-ready resolution. Without watermark or courtesy text.',
                    'fr' => 'Obtenez le Pack Complet : tous les formats, fonds d\'écran mobile et bureau, qualité HD et résolution impression. Sans filigrane.',
                    'pt' => 'Obtenha o Pack Completo: todos os formatos, wallpapers para móvel e desktop, qualidade HD e resolução impressão. Sem marca de água.',
                    'it' => 'Ottieni il Pack Completo: tutti i formati, sfondi mobile e desktop, qualità HD e risoluzione stampa. Senza filigrana.',
                    default => 'Consigue el Pack Completo: todos los formatos, fondos de pantalla para móvil y escritorio, calidad HD y resolución de impresión. Sin marca de agua ni texto de cortesía.',
                } }}
            </p>

            {{-- Cupón --}}
            <div class="bg-champagne-dark rounded-xl p-5 mb-5 border-l-4 border-burgundy">
                <p class="text-sm text-pmf-text">
                    {{ match($locale) {
                        'en' => 'Use coupon',
                        'fr' => 'Utilisez le coupon',
                        'pt' => 'Use o cupão',
                        'it' => 'Usa il coupon',
                        default => 'Usa el cupón',
                    } }}
                    <strong class="text-burgundy text-xl tracking-wider mx-1">PROMO50</strong>
                    {{ match($locale) {
                        'en' => 'and get 50% off — from €9.99 to',
                        'fr' => 'et obtenez -50% — de 9,99€ à',
                        'pt' => 'e obtenha 50% — de 9,99€ a',
                        'it' => 'e ottieni il 50% — da 9,99€ a',
                        default => 'y obtén un 50% — de 9,99€ a',
                    } }}
                    <strong class="text-burgundy text-lg">4,99€</strong>
                </p>
            </div>

            <a href="{{ $upsellUrl }}" class="inline-block bg-burgundy text-white font-bold text-base px-8 py-4 rounded-lg hover:bg-burgundy-dark transition tracking-wide">
                {{ match($locale) {
                    'en' => 'Get Complete Pack at 50% off →',
                    'fr' => 'Obtenir le Pack Complet à -50% →',
                    'pt' => 'Obter Pack Completo com 50% →',
                    'it' => 'Ottieni Pack Completo al 50% →',
                    default => 'Conseguir Pack Completo al 50% →',
                } }}
            </a>

            <p class="text-xs text-pmf-muted mt-3">{{ __('ui.hero_no_account') }}</p>
        </div>

    </div>
</section>
@endsection
