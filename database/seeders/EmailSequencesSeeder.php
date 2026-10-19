<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailSequencesSeeder extends Seeder
{
    public function run(): void
    {
        $sequences = [
            // --- Abandonment ---
            [
                'sequence_key' => 'abandonment_1', 'sequence_type' => 'abandonment',
                'name' => 'Recordatorio suave (2h)', 'delay_hours' => 2, 'email_number' => 1,
                'discount_percent' => 0, 'discount_product_key' => null,
                'description' => 'Primer contacto tras 2h sin compra.',
                'subject' => json_encode([
                    'es' => 'Tu retrato sigue esperándote — GenPic',
                    'en' => 'Your portrait is still waiting — GenPic',
                    'fr' => 'Votre portrait vous attend toujours — GenPic',
                    'pt' => 'O seu retrato ainda está à sua espera — GenPic',
                    'it' => 'Il tuo ritratto ti sta ancora aspettando — GenPic',
                ]),
                'heading' => json_encode([
                    'es' => 'Tu retrato sigue esperándote',
                    'en' => 'Your portrait is still waiting',
                    'fr' => 'Votre portrait vous attend toujours',
                    'pt' => 'O seu retrato ainda está à sua espera',
                    'it' => 'Il tuo ritratto ti sta ancora aspettando',
                ]),
                'body' => json_encode([
                    'es' => '<p>Hemos creado tu retrato artístico y está listo para que lo veas.</p><p>No pierdas la oportunidad de tenerlo.</p>',
                    'en' => '<p>We\'ve created your artistic portrait and it\'s ready for you.</p><p>Don\'t miss the chance to have it.</p>',
                    'fr' => '<p>Nous avons créé votre portrait artistique et il est prêt.</p><p>Ne manquez pas l\'occasion de l\'avoir.</p>',
                    'pt' => '<p>Criámos o seu retrato artístico e está pronto para si.</p><p>Não perca a oportunidade de o ter.</p>',
                    'it' => '<p>Abbiamo creato il tuo ritratto artistico ed è pronto per te.</p><p>Non perdere l\'occasione di averlo.</p>',
                ]),
                'cta_text' => json_encode([
                    'es' => 'Ver mi retrato',
                    'en' => 'View my portrait',
                    'fr' => 'Voir mon portrait',
                    'pt' => 'Ver o meu retrato',
                    'it' => 'Vedi il mio ritratto',
                ]),
                'coupon_text' => null, 'active' => true,
            ],
            [
                'sequence_key' => 'abandonment_2', 'sequence_type' => 'abandonment',
                'name' => 'Beneficios (24h)', 'delay_hours' => 24, 'email_number' => 2,
                'discount_percent' => 0, 'discount_product_key' => null,
                'description' => 'Segundo contacto a las 24h. Beneficios versión sin marca de agua.',
                'subject' => json_encode([
                    'es' => '¿Te gusta tu retrato? Descárgalo sin marca de agua — GenPic',
                    'en' => 'Like your portrait? Download it without watermark — GenPic',
                    'fr' => 'Vous aimez votre portrait ? Téléchargez-le sans filigrane — GenPic',
                    'pt' => 'Gosta do seu retrato? Descarregue-o sem marca de água — GenPic',
                    'it' => 'Ti piace il tuo ritratto? Scaricalo senza filigrana — GenPic',
                ]),
                'heading' => json_encode([
                    'es' => '¿Te gusta tu retrato?',
                    'en' => 'Like your portrait?',
                    'fr' => 'Vous aimez votre portrait ?',
                    'pt' => 'Gosta do seu retrato?',
                    'it' => 'Ti piace il tuo ritratto?',
                ]),
                'body' => json_encode([
                    'es' => '<p>Tu retrato artístico quedó genial.</p><p><strong>Descárgalo sin marca de agua</strong> y disfrútalo en la mejor calidad.</p>',
                    'en' => '<p>Your artistic portrait looks amazing.</p><p><strong>Download it without watermark</strong> and enjoy it in the best quality.</p>',
                    'fr' => '<p>Votre portrait artistique est superbe.</p><p><strong>Téléchargez-le sans filigrane</strong> et profitez-en en meilleure qualité.</p>',
                    'pt' => '<p>O seu retrato artístico ficou incrível.</p><p><strong>Descarregue-o sem marca de água</strong> e desfrute da melhor qualidade.</p>',
                    'it' => '<p>Il tuo ritratto artistico è fantastico.</p><p><strong>Scaricalo senza filigrana</strong> e goditi la migliore qualità.</p>',
                ]),
                'cta_text' => json_encode([
                    'es' => 'Descargar sin marca de agua',
                    'en' => 'Download without watermark',
                    'fr' => 'Télécharger sans filigrane',
                    'pt' => 'Descarregar sem marca de água',
                    'it' => 'Scarica senza filigrana',
                ]),
                'coupon_text' => null, 'active' => true,
            ],
            [
                'sequence_key' => 'abandonment_3', 'sequence_type' => 'abandonment',
                'name' => 'Descuento 50% (3 días)', 'delay_hours' => 72, 'email_number' => 3,
                'discount_percent' => 50, 'discount_product_key' => 'pack_completo',
                'description' => 'Tercer contacto a los 3 días. 50% descuento Pack Completo.',
                'subject' => json_encode([
                    'es' => '50% de descuento en tu retrato — solo para ti — GenPic',
                    'en' => '50% off your portrait — just for you — GenPic',
                    'fr' => '50% de réduction sur votre portrait — rien que pour vous — GenPic',
                    'pt' => '50% de desconto no seu retrato — só para si — GenPic',
                    'it' => '50% di sconto sul tuo ritratto — solo per te — GenPic',
                ]),
                'heading' => json_encode([
                    'es' => '50% de descuento — solo para ti',
                    'en' => '50% off — just for you',
                    'fr' => '50% de réduction — rien que pour vous',
                    'pt' => '50% de desconto — só para si',
                    'it' => '50% di sconto — solo per te',
                ]),
                'body' => json_encode([
                    'es' => '<p>Queremos que tengas tu retrato.</p><p>Por eso te ofrecemos un <strong>50% de descuento exclusivo</strong> en el Pack Completo. Usa el código <strong>FAMILY</strong> en el checkout.</p>',
                    'en' => '<p>We want you to have your portrait.</p><p>That\'s why we\'re offering you an <strong>exclusive 50% off</strong> the Complete Pack. Use code <strong>FAMILY</strong> at checkout.</p>',
                    'fr' => '<p>Nous voulons que vous ayez votre portrait.</p><p>C\'est pourquoi nous vous offrons une <strong>réduction exclusive de 50%</strong> sur le Pack Complet. Utilisez le code <strong>FAMILY</strong> au moment du paiement.</p>',
                    'pt' => '<p>Queremos que tenha o seu retrato.</p><p>Por isso oferecemos-lhe um <strong>desconto exclusivo de 50%</strong> no Pack Completo. Use o código <strong>FAMILY</strong> no checkout.</p>',
                    'it' => '<p>Vogliamo che tu abbia il tuo ritratto.</p><p>Per questo ti offriamo uno <strong>sconto esclusivo del 50%</strong> sul Pacchetto Completo. Usa il codice <strong>FAMILY</strong> al checkout.</p>',
                ]),
                'cta_text' => json_encode([
                    'es' => 'Usar mi descuento del 50%',
                    'en' => 'Use my 50% discount',
                    'fr' => 'Utiliser ma réduction de 50%',
                    'pt' => 'Usar o meu desconto de 50%',
                    'it' => 'Usa il mio sconto del 50%',
                ]),
                'coupon_text' => json_encode([
                    'es' => 'Introduce el código FAMILY en el checkout para aplicar el descuento.',
                    'en' => 'Enter code FAMILY at checkout to apply your discount.',
                    'fr' => 'Entrez le code FAMILY au moment du paiement pour appliquer la réduction.',
                    'pt' => 'Insira o código FAMILY no checkout para aplicar o desconto.',
                    'it' => 'Inserisci il codice FAMILY al checkout per applicare lo sconto.',
                ]),
                'active' => true,
            ],
            [
                'sequence_key' => 'abandonment_4', 'sequence_type' => 'abandonment',
                'name' => 'Última oportunidad (7 días)', 'delay_hours' => 168, 'email_number' => 4,
                'discount_percent' => 50, 'discount_product_key' => 'pack_completo',
                'description' => 'Último contacto 7d. Urgencia expiración + 50%.',
                'subject' => json_encode([
                    'es' => 'Última oportunidad — tu retrato se borra en 7 días — GenPic',
                    'en' => 'Last chance — your portrait will be deleted in 7 days — GenPic',
                    'fr' => 'Dernière chance — votre portrait sera supprimé dans 7 jours — GenPic',
                    'pt' => 'Última oportunidade — o seu retrato será apagado em 7 dias — GenPic',
                    'it' => 'Ultima occasione — il tuo ritratto sarà cancellato tra 7 giorni — GenPic',
                ]),
                'heading' => json_encode([
                    'es' => 'Última oportunidad',
                    'en' => 'Last chance',
                    'fr' => 'Dernière chance',
                    'pt' => 'Última oportunidade',
                    'it' => 'Ultima occasione',
                ]),
                'body' => json_encode([
                    'es' => '<p>Tu retrato <strong>se borrará en :days días</strong>.</p><p>Aprovecha el 50% de descuento con el código <strong>FAMILY</strong> antes de que sea tarde.</p>',
                    'en' => '<p>Your portrait <strong>will be deleted in :days days</strong>.</p><p>Take advantage of 50% off with code <strong>FAMILY</strong> before it\'s too late.</p>',
                    'fr' => '<p>Votre portrait <strong>sera supprimé dans :days jours</strong>.</p><p>Profitez de 50% de réduction avec le code <strong>FAMILY</strong> avant qu\'il ne soit trop tard.</p>',
                    'pt' => '<p>O seu retrato <strong>será apagado em :days dias</strong>.</p><p>Aproveite 50% de desconto com o código <strong>FAMILY</strong> antes que seja tarde.</p>',
                    'it' => '<p>Il tuo ritratto <strong>sarà cancellato tra :days giorni</strong>.</p><p>Approfitta del 50% di sconto con il codice <strong>FAMILY</strong> prima che sia troppo tardi.</p>',
                ]),
                'cta_text' => json_encode([
                    'es' => 'Aprovechar antes de que expire',
                    'en' => 'Get it before it expires',
                    'fr' => 'En profiter avant expiration',
                    'pt' => 'Aproveitar antes que expire',
                    'it' => 'Approfittane prima che scada',
                ]),
                'coupon_text' => json_encode([
                    'es' => 'Introduce el código FAMILY en el checkout.',
                    'en' => 'Enter code FAMILY at checkout.',
                    'fr' => 'Entrez le code FAMILY au moment du paiement.',
                    'pt' => 'Insira o código FAMILY no checkout.',
                    'it' => 'Inserisci il codice FAMILY al checkout.',
                ]),
                'active' => true,
            ],
            // --- Upgrade ---
            [
                'sequence_key' => 'upgrade_1', 'sequence_type' => 'upgrade',
                'name' => 'Upgrade sin descuento (48h)', 'delay_hours' => 48, 'email_number' => 1,
                'discount_percent' => 0, 'discount_product_key' => null,
                'description' => 'Primer contacto upgrade 48h. Beneficios Pack Completo.',
                'subject' => json_encode([
                    'es' => 'Mejora tu retrato — todos los formatos disponibles — GenPic',
                    'en' => 'Upgrade your portrait — all formats available — GenPic',
                    'fr' => 'Améliorez votre portrait — tous les formats disponibles — GenPic',
                    'pt' => 'Melhore o seu retrato — todos os formatos disponíveis — GenPic',
                    'it' => 'Migliora il tuo ritratto — tutti i formati disponibili — GenPic',
                ]),
                'heading' => json_encode([
                    'es' => 'Mejora tu retrato',
                    'en' => 'Upgrade your portrait',
                    'fr' => 'Améliorez votre portrait',
                    'pt' => 'Melhore o seu retrato',
                    'it' => 'Migliora il tuo ritratto',
                ]),
                'body' => json_encode([
                    'es' => '<p>Compraste <strong>:product</strong> y quedó genial.</p><p>Con el Pack Completo tendrías todos los formatos: fondos de pantalla, alta calidad para imprimir y mucho más.</p>',
                    'en' => '<p>You purchased <strong>:product</strong> and it looks great.</p><p>With the Complete Pack you\'d get all formats: wallpapers, print-quality and much more.</p>',
                    'fr' => '<p>Vous avez acheté <strong>:product</strong> et c\'est superbe.</p><p>Avec le Pack Complet, vous auriez tous les formats : fonds d\'écran, haute qualité pour imprimer et bien plus.</p>',
                    'pt' => '<p>Comprou <strong>:product</strong> e ficou ótimo.</p><p>Com o Pack Completo teria todos os formatos: fundos de ecrã, alta qualidade para imprimir e muito mais.</p>',
                    'it' => '<p>Hai acquistato <strong>:product</strong> ed è fantastico.</p><p>Con il Pacchetto Completo avresti tutti i formati: sfondi, alta qualità per stampare e molto altro.</p>',
                ]),
                'cta_text' => json_encode([
                    'es' => 'Ver el Pack Completo',
                    'en' => 'See the Complete Pack',
                    'fr' => 'Voir le Pack Complet',
                    'pt' => 'Ver o Pack Completo',
                    'it' => 'Vedi il Pacchetto Completo',
                ]),
                'coupon_text' => null, 'active' => true,
            ],
            [
                'sequence_key' => 'upgrade_2', 'sequence_type' => 'upgrade',
                'name' => 'Upgrade 50% descuento (5 días)', 'delay_hours' => 120, 'email_number' => 2,
                'discount_percent' => 50, 'discount_product_key' => 'pack_completo',
                'description' => 'Segundo contacto 5d. 50% Pack Completo para clientes.',
                'subject' => json_encode([
                    'es' => '50% en Pack Completo — exclusivo para clientes — GenPic',
                    'en' => '50% off Complete Pack — exclusive for customers — GenPic',
                    'fr' => '50% sur le Pack Complet — exclusif pour les clients — GenPic',
                    'pt' => '50% no Pack Completo — exclusivo para clientes — GenPic',
                    'it' => '50% sul Pacchetto Completo — esclusivo per i clienti — GenPic',
                ]),
                'heading' => json_encode([
                    'es' => '50% en el Pack Completo — exclusivo para ti',
                    'en' => '50% off Complete Pack — exclusive for you',
                    'fr' => '50% sur le Pack Complet — exclusif pour vous',
                    'pt' => '50% no Pack Completo — exclusivo para si',
                    'it' => '50% sul Pacchetto Completo — esclusivo per te',
                ]),
                'body' => json_encode([
                    'es' => '<p>Como cliente, te ofrecemos un <strong>50% exclusivo</strong> en el Pack Completo.</p><p>Todos los formatos de tu retrato por la mitad de precio. Usa el código <strong>FAMILY</strong> en el checkout.</p>',
                    'en' => '<p>As a customer, we\'re offering you an <strong>exclusive 50% off</strong> the Complete Pack.</p><p>All formats of your portrait for half the price. Use code <strong>FAMILY</strong> at checkout.</p>',
                    'fr' => '<p>En tant que client, nous vous offrons <strong>50% exclusif</strong> sur le Pack Complet.</p><p>Tous les formats de votre portrait à moitié prix. Utilisez le code <strong>FAMILY</strong> au moment du paiement.</p>',
                    'pt' => '<p>Como cliente, oferecemos-lhe <strong>50% exclusivo</strong> no Pack Completo.</p><p>Todos os formatos do seu retrato por metade do preço. Use o código <strong>FAMILY</strong> no checkout.</p>',
                    'it' => '<p>Come cliente, ti offriamo uno <strong>sconto esclusivo del 50%</strong> sul Pacchetto Completo.</p><p>Tutti i formati del tuo ritratto a metà prezzo. Usa il codice <strong>FAMILY</strong> al checkout.</p>',
                ]),
                'cta_text' => json_encode([
                    'es' => 'Usar mi 50% de descuento',
                    'en' => 'Use my 50% discount',
                    'fr' => 'Utiliser ma réduction de 50%',
                    'pt' => 'Usar o meu desconto de 50%',
                    'it' => 'Usa il mio sconto del 50%',
                ]),
                'coupon_text' => json_encode([
                    'es' => 'Introduce el código FAMILY en el checkout. Tu descuento exclusivo de cliente.',
                    'en' => 'Enter code FAMILY at checkout. Your exclusive customer discount.',
                    'fr' => 'Entrez le code FAMILY au moment du paiement. Votre réduction exclusive.',
                    'pt' => 'Insira o código FAMILY no checkout. Seu desconto exclusivo.',
                    'it' => 'Inserisci il codice FAMILY al checkout. Il tuo sconto esclusivo.',
                ]),
                'active' => true,
            ],
        ];

        foreach ($sequences as $seq) {
            DB::table('email_sequences')->updateOrInsert(
                ['sequence_key' => $seq['sequence_key']],
                array_merge($seq, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }
}
