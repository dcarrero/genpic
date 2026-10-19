<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MailCampaignTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Bloque HTML del cupón por idioma
        $couponBlock = [
            'es' => '<h3 style="color:#7A2D3B;margin:24px 0 8px 0;">🎟 Oferta exclusiva para suscriptores de face.co</h3>'
                . '<p>Usa el cupón <strong style="font-size:20px;color:#7A2D3B;letter-spacing:1px;">FACECO</strong> en el checkout y obtén un <strong>50% de descuento</strong> en:</p>'
                . '<ul><li><strong>Pack Fondos de Pantalla</strong> (móvil + escritorio) — de 4,99€ a <strong style="color:#7A2D3B;">2,49€</strong></li>'
                . '<li><strong>Pack Completo</strong> (todos los formatos + fondos + HD + impresión) — de 9,99€ a <strong style="color:#7A2D3B;">4,99€</strong></li></ul>',

            'en' => '<h3 style="color:#7A2D3B;margin:24px 0 8px 0;">🎟 Exclusive offer for face.co subscribers</h3>'
                . '<p>Use coupon <strong style="font-size:20px;color:#7A2D3B;letter-spacing:1px;">FACECO</strong> at checkout and get <strong>50% off</strong>:</p>'
                . '<ul><li><strong>Wallpaper Pack</strong> (mobile + desktop) — from €4.99 to <strong style="color:#7A2D3B;">€2.49</strong></li>'
                . '<li><strong>Complete Pack</strong> (all formats + wallpapers + HD + print) — from €9.99 to <strong style="color:#7A2D3B;">€4.99</strong></li></ul>',

            'fr' => '<h3 style="color:#7A2D3B;margin:24px 0 8px 0;">🎟 Offre exclusive pour les abonnés face.co</h3>'
                . '<p>Utilisez le coupon <strong style="font-size:20px;color:#7A2D3B;letter-spacing:1px;">FACECO</strong> lors du paiement et obtenez <strong>50% de réduction</strong> sur :</p>'
                . '<ul><li><strong>Pack Fonds d\'écran</strong> (mobile + bureau) — de 4,99€ à <strong style="color:#7A2D3B;">2,49€</strong></li>'
                . '<li><strong>Pack Complet</strong> (tous les formats + fonds + HD + impression) — de 9,99€ à <strong style="color:#7A2D3B;">4,99€</strong></li></ul>',

            'pt' => '<h3 style="color:#7A2D3B;margin:24px 0 8px 0;">🎟 Oferta exclusiva para subscritores do face.co</h3>'
                . '<p>Use o cupão <strong style="font-size:20px;color:#7A2D3B;letter-spacing:1px;">FACECO</strong> no checkout e obtenha <strong>50% de desconto</strong> em:</p>'
                . '<ul><li><strong>Pack Wallpapers</strong> (móvel + desktop) — de 4,99€ a <strong style="color:#7A2D3B;">2,49€</strong></li>'
                . '<li><strong>Pack Completo</strong> (todos os formatos + wallpapers + HD + impressão) — de 9,99€ a <strong style="color:#7A2D3B;">4,99€</strong></li></ul>',

            'it' => '<h3 style="color:#7A2D3B;margin:24px 0 8px 0;">🎟 Offerta esclusiva per gli iscritti face.co</h3>'
                . '<p>Usa il coupon <strong style="font-size:20px;color:#7A2D3B;letter-spacing:1px;">FACECO</strong> al checkout e ottieni il <strong>50% di sconto</strong> su:</p>'
                . '<ul><li><strong>Pack Sfondi</strong> (mobile + desktop) — da 4,99€ a <strong style="color:#7A2D3B;">2,49€</strong></li>'
                . '<li><strong>Pack Completo</strong> (tutti i formati + sfondi + HD + stampa) — da 9,99€ a <strong style="color:#7A2D3B;">4,99€</strong></li></ul>',
        ];

        $campaigns = [
            [
                'name' => 'face.co → GenPic — Invitación inicial',
                'subject' => json_encode([
                    'es' => '🎨 Tu avatar de face.co ahora puede ser un cuadro — prueba GenPic gratis',
                    'en' => '🎨 Your face.co avatar can now be a painting — try GenPic free',
                    'fr' => '🎨 Votre avatar face.co peut devenir un tableau — essayez GenPic gratuitement',
                    'pt' => '🎨 O seu avatar face.co agora pode ser um quadro — experimente GenPic grátis',
                    'it' => '🎨 Il tuo avatar face.co ora può essere un dipinto — prova GenPic gratis',
                ]),
                'body' => json_encode([
                    'es' => '<p>Desde el equipo de <strong>face.co</strong> te escribimos para presentarte <strong>GenPic</strong>, nuestro nuevo proyecto hermano.</p>'
                        . '<p>Si te gustó crear avatares en face.co, GenPic te va a encantar: <strong>sube cualquier foto real</strong> (tuya, de tu familia, amigos o pareja) y nuestra IA la transforma en un <strong>cuadro al óleo, un retrato renacentista, una foto profesional para LinkedIn</strong> o lo que imagines.</p>'
                        . '<p><strong>73 estilos disponibles</strong> — artísticos, profesionales, temáticos y de aventura.</p>'
                        . '<p>La vista previa es <strong>gratis</strong>. Solo pagas si quieres descargar sin marca de agua.</p>'
                        . $couponBlock['es'],
                    'en' => '<p>The <strong>face.co</strong> team is writing to introduce you to <strong>GenPic</strong>, our new sister project.</p>'
                        . '<p>If you enjoyed creating avatars on face.co, you\'ll love GenPic: <strong>upload any real photo</strong> (of yourself, family, friends or partner) and our AI transforms it into an <strong>oil painting, Renaissance portrait, professional LinkedIn headshot</strong> or whatever you imagine.</p>'
                        . '<p><strong>73 styles available</strong> — artistic, professional, themed and adventure.</p>'
                        . '<p>The preview is <strong>free</strong>. You only pay if you want to download without watermark.</p>'
                        . $couponBlock['en'],
                    'fr' => '<p>L\'équipe de <strong>face.co</strong> vous écrit pour vous présenter <strong>GenPic</strong>, notre nouveau projet frère.</p>'
                        . '<p>Si vous avez aimé créer des avatars sur face.co, GenPic va vous plaire : <strong>envoyez n\'importe quelle photo réelle</strong> (de vous, votre famille, amis ou partenaire) et notre IA la transforme en <strong>peinture à l\'huile, portrait Renaissance, photo professionnelle LinkedIn</strong> ou ce que vous imaginez.</p>'
                        . '<p><strong>73 styles disponibles</strong> — artistiques, professionnels, thématiques et aventure.</p>'
                        . '<p>L\'aperçu est <strong>gratuit</strong>. Vous ne payez que si vous souhaitez télécharger sans filigrane.</p>'
                        . $couponBlock['fr'],
                    'pt' => '<p>A equipa do <strong>face.co</strong> escreve-lhe para apresentar o <strong>GenPic</strong>, o nosso novo projeto irmão.</p>'
                        . '<p>Se gostou de criar avatares no face.co, vai adorar o GenPic: <strong>envie qualquer foto real</strong> (sua, da família, amigos ou parceiro/a) e a nossa IA transforma-a num <strong>quadro a óleo, retrato renascentista, foto profissional para LinkedIn</strong> ou o que imaginar.</p>'
                        . '<p><strong>73 estilos disponíveis</strong> — artísticos, profissionais, temáticos e aventura.</p>'
                        . '<p>A pré-visualização é <strong>gratuita</strong>. Só paga se quiser descarregar sem marca de água.</p>'
                        . $couponBlock['pt'],
                    'it' => '<p>Il team di <strong>face.co</strong> ti scrive per presentarti <strong>GenPic</strong>, il nostro nuovo progetto fratello.</p>'
                        . '<p>Se ti è piaciuto creare avatar su face.co, GenPic ti piacerà ancora di più: <strong>carica qualsiasi foto reale</strong> (tua, della famiglia, amici o partner) e la nostra IA la trasforma in un <strong>dipinto a olio, ritratto rinascimentale, foto professionale per LinkedIn</strong> o quello che immagini.</p>'
                        . '<p><strong>73 stili disponibili</strong> — artistici, professionali, tematici e avventura.</p>'
                        . '<p>L\'anteprima è <strong>gratuita</strong>. Paghi solo se vuoi scaricare senza filigrana.</p>'
                        . $couponBlock['it'],
                ]),
                'coupon_code' => 'FACECO',
                'target_source' => 'faceco',
                'status' => 'draft',
                'batch_size' => 100,
            ],

            [
                'name' => 'face.co → GenPic — Recordatorio (2ª oleada)',
                'subject' => json_encode([
                    'es' => '🖼 ¿Ya probaste GenPic? Tu 50% de descuento sigue activo',
                    'en' => '🖼 Have you tried GenPic yet? Your 50% off is still active',
                    'fr' => '🖼 Avez-vous essayé GenPic ? Vos -50% sont toujours actifs',
                    'pt' => '🖼 Já experimentou GenPic? Os seus 50% de desconto continuam ativos',
                    'it' => '🖼 Hai già provato GenPic? Il tuo 50% di sconto è ancora attivo',
                ]),
                'body' => json_encode([
                    'es' => '<p>Hace unos días te presentamos <strong>GenPic</strong>, el proyecto hermano de face.co que convierte tus fotos en obras de arte con IA.</p>'
                        . '<p>Si aún no lo has probado, aquí tienes algunas ideas:</p>'
                        . '<ul><li><strong>📸 Foto profesional para LinkedIn</strong> — lista en 60 segundos</li>'
                        . '<li><strong>🎨 Cuadro al óleo de tu familia</strong> — el regalo perfecto</li>'
                        . '<li><strong>🏄 Fotos de aventura</strong> — surfista, esquiador, futbolista...</li>'
                        . '<li><strong>💝 Fotos románticas</strong> — para tu pareja o perfil de citas</li></ul>'
                        . '<p>La <strong>vista previa es gratis</strong>.</p>'
                        . $couponBlock['es'],
                    'en' => '<p>A few days ago we introduced you to <strong>GenPic</strong>, the sister project of face.co that turns your photos into AI art.</p>'
                        . '<p>If you haven\'t tried it yet, here are some ideas:</p>'
                        . '<ul><li><strong>📸 Professional LinkedIn photo</strong> — ready in 60 seconds</li>'
                        . '<li><strong>🎨 Oil painting of your family</strong> — the perfect gift</li>'
                        . '<li><strong>🏄 Adventure photos</strong> — surfer, skier, footballer...</li>'
                        . '<li><strong>💝 Romantic photos</strong> — for your partner or dating profile</li></ul>'
                        . '<p>The <strong>preview is free</strong>.</p>'
                        . $couponBlock['en'],
                    'fr' => '<p>Il y a quelques jours, nous vous avons présenté <strong>GenPic</strong>, le projet frère de face.co qui transforme vos photos en art avec l\'IA.</p>'
                        . '<p>Si vous ne l\'avez pas encore essayé, voici quelques idées :</p>'
                        . '<ul><li><strong>📸 Photo professionnelle LinkedIn</strong> — prête en 60 secondes</li>'
                        . '<li><strong>🎨 Peinture à l\'huile de votre famille</strong> — le cadeau parfait</li>'
                        . '<li><strong>🏄 Photos d\'aventure</strong> — surfeur, skieur, footballeur...</li>'
                        . '<li><strong>💝 Photos romantiques</strong> — pour votre partenaire ou profil de rencontre</li></ul>'
                        . '<p>L\'<strong>aperçu est gratuit</strong>.</p>'
                        . $couponBlock['fr'],
                    'pt' => '<p>Há uns dias apresentámos-lhe o <strong>GenPic</strong>, o projeto irmão do face.co que transforma as suas fotos em arte com IA.</p>'
                        . '<p>Se ainda não experimentou, aqui tem algumas ideias:</p>'
                        . '<ul><li><strong>📸 Foto profissional LinkedIn</strong> — pronta em 60 segundos</li>'
                        . '<li><strong>🎨 Quadro a óleo da sua família</strong> — o presente perfeito</li>'
                        . '<li><strong>🏄 Fotos de aventura</strong> — surfista, esquiador, futebolista...</li>'
                        . '<li><strong>💝 Fotos românticas</strong> — para o seu par ou perfil de encontros</li></ul>'
                        . '<p>A <strong>pré-visualização é gratuita</strong>.</p>'
                        . $couponBlock['pt'],
                    'it' => '<p>Qualche giorno fa ti abbiamo presentato <strong>GenPic</strong>, il progetto fratello di face.co che trasforma le tue foto in arte con l\'IA.</p>'
                        . '<p>Se non l\'hai ancora provato, ecco alcune idee:</p>'
                        . '<ul><li><strong>📸 Foto professionale LinkedIn</strong> — pronta in 60 secondi</li>'
                        . '<li><strong>🎨 Dipinto a olio della tua famiglia</strong> — il regalo perfetto</li>'
                        . '<li><strong>🏄 Foto d\'avventura</strong> — surfista, sciatore, calciatore...</li>'
                        . '<li><strong>💝 Foto romantiche</strong> — per il tuo partner o profilo di incontri</li></ul>'
                        . '<p>L\'<strong>anteprima è gratuita</strong>.</p>'
                        . $couponBlock['it'],
                ]),
                'coupon_code' => 'FACECO',
                'target_source' => 'faceco',
                'status' => 'draft',
                'batch_size' => 100,
            ],
        ];

        foreach ($campaigns as $campaign) {
            DB::table('mail_campaigns')->updateOrInsert(
                ['name' => $campaign['name']],
                array_merge($campaign, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
