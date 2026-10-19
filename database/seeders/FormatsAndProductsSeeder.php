<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormatsAndProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Formatos — deben coincidir con config/genpic.php 'formats'
        $formats = [
            [
                'format_key'      => 'web_basic',
                'label'           => json_encode(['es' => 'Web Básica', 'en' => 'Web Basic', 'fr' => 'Web Basique', 'pt' => 'Web Básica', 'it' => 'Web Base']),
                'label_es'        => 'Web Básica',
                'label_en'        => 'Web Basic',
                'max_dimension_px' => 1024,
                'file_type'       => 'jpg',
                'quality'         => 85,
            ],
            [
                'format_key'      => 'web_hd',
                'label'           => json_encode(['es' => 'Web HD', 'en' => 'Web HD', 'fr' => 'Web HD', 'pt' => 'Web HD', 'it' => 'Web HD']),
                'label_es'        => 'Web HD',
                'label_en'        => 'Web HD',
                'max_dimension_px' => 2048,
                'file_type'       => 'jpg',
                'quality'         => 90,
            ],
            [
                'format_key'      => 'mobile_wallpaper',
                'label'           => json_encode(['es' => 'Fondo de Móvil', 'en' => 'Mobile Wallpaper', 'fr' => "Fond d'Écran Mobile", 'pt' => 'Fundo de Telemóvel', 'it' => 'Sfondo Cellulare']),
                'label_es'        => 'Fondo de Móvil',
                'label_en'        => 'Mobile Wallpaper',
                'width_px'        => 1080,
                'height_px'       => 2340,
                'file_type'       => 'jpg',
                'quality'         => 90,
            ],
            [
                'format_key'      => 'desktop_wallpaper',
                'label'           => json_encode(['es' => 'Fondo de Escritorio', 'en' => 'Desktop Wallpaper', 'fr' => "Fond d'Écran Bureau", 'pt' => 'Fundo de Ambiente de Trabalho', 'it' => 'Sfondo Desktop']),
                'label_es'        => 'Fondo de Escritorio',
                'label_en'        => 'Desktop Wallpaper',
                'width_px'        => 2560,
                'height_px'       => 1440,
                'file_type'       => 'jpg',
                'quality'         => 90,
            ],
            [
                'format_key'      => 'print_premium',
                'label'           => json_encode(['es' => 'PNG Alta Calidad', 'en' => 'High Quality PNG', 'fr' => 'PNG Haute Qualité', 'pt' => 'PNG Alta Qualidade', 'it' => 'PNG Alta Qualità']),
                'label_es'        => 'PNG Alta Calidad',
                'label_en'        => 'High Quality PNG',
                'max_dimension_px' => 4096,
                'file_type'       => 'png',
                'quality'         => 100,
            ],
        ];

        foreach ($formats as $format) {
            DB::table('formats')->updateOrInsert(
                ['format_key' => $format['format_key']],
                array_merge($format, ['updated_at' => now(), 'created_at' => now()])
            );
        }

        // Productos — 4 productos con precios actuales (EUR/USD en céntimos)
        $products = [
            [
                'product_key'    => 'web_basic',
                'name'           => json_encode(['es' => 'Web Básica', 'en' => 'Web Basic', 'fr' => 'Web Basique', 'pt' => 'Web Básica', 'it' => 'Web Base']),
                'name_es'        => 'Web Básica',
                'name_en'        => 'Web Basic',
                'description'    => json_encode(['es' => 'JPG optimizado, 1024px. Para redes sociales y WhatsApp.', 'en' => 'Optimized JPG, 1024px. For social media and messaging.', 'fr' => 'JPG optimisé, 1024px. Pour les réseaux sociaux et la messagerie.', 'pt' => 'JPG otimizado, 1024px. Para redes sociais e mensagens.', 'it' => 'JPG ottimizzato, 1024px. Per social media e messaggistica.']),
                'description_es' => 'JPG optimizado, 1024px. Para redes sociales y WhatsApp.',
                'description_en' => 'Optimized JPG, 1024px. For social media and messaging.',
                'price_es_cents' => 199,
                'price_en_cents' => 249,
                'currency_es'    => 'eur',
                'currency_en'    => 'usd',
                'sort_order'     => 1,
                'highlight'      => false,
                'active'         => true,
            ],
            [
                'product_key'    => 'web_hd',
                'name'           => json_encode(['es' => 'Web HD', 'en' => 'Web HD', 'fr' => 'Web HD', 'pt' => 'Web HD', 'it' => 'Web HD']),
                'name_es'        => 'Web HD',
                'name_en'        => 'Web HD',
                'description'    => json_encode(['es' => 'JPG alta calidad, 2048px. Perfecto para compartir e imprimir pequeño.', 'en' => 'High quality JPG, 2048px. Perfect for sharing and small prints.', 'fr' => 'JPG haute qualité, 2048px. Parfait pour partager et imprimer en petit.', 'pt' => 'JPG alta qualidade, 2048px. Perfeito para partilhar e imprimir em pequeno.', 'it' => 'JPG alta qualità, 2048px. Perfetto per condividere e stampare in piccolo.']),
                'description_es' => 'JPG alta calidad, 2048px. Perfecto para compartir e imprimir pequeño.',
                'description_en' => 'High quality JPG, 2048px. Perfect for sharing and small prints.',
                'price_es_cents' => 399,
                'price_en_cents' => 499,
                'currency_es'    => 'eur',
                'currency_en'    => 'usd',
                'sort_order'     => 2,
                'highlight'      => false,
                'active'         => true,
            ],
            [
                'product_key'    => 'wallpaper_pack',
                'name'           => json_encode(['es' => 'Pack Fondos de Pantalla', 'en' => 'Wallpaper Pack', 'fr' => "Pack Fonds d'Écran", 'pt' => 'Pack Fundos de Ecrã', 'it' => 'Pack Sfondi']),
                'name_es'        => 'Pack Fondos de Pantalla',
                'name_en'        => 'Wallpaper Pack',
                'description'    => json_encode(['es' => 'Fondo de móvil (1080×2340) + Fondo de escritorio (2560×1440).', 'en' => 'Mobile wallpaper (1080×2340) + Desktop wallpaper (2560×1440).', 'fr' => "Fond d'écran mobile (1080×2340) + Fond d'écran bureau (2560×1440).", 'pt' => 'Fundo de telemóvel (1080×2340) + Fundo de ambiente de trabalho (2560×1440).', 'it' => 'Sfondo cellulare (1080×2340) + Sfondo desktop (2560×1440).']),
                'description_es' => 'Fondo de móvil (1080×2340) + Fondo de escritorio (2560×1440).',
                'description_en' => 'Mobile wallpaper (1080×2340) + Desktop wallpaper (2560×1440).',
                'price_es_cents' => 799,
                'price_en_cents' => 899,
                'currency_es'    => 'eur',
                'currency_en'    => 'usd',
                'sort_order'     => 3,
                'highlight'      => true,
                'active'         => true,
            ],
            [
                'product_key'    => 'pack_completo',
                'name'           => json_encode(['es' => 'Pack Completo', 'en' => 'Complete Pack', 'fr' => 'Pack Complet', 'pt' => 'Pack Completo', 'it' => 'Pacchetto Completo']),
                'name_es'        => 'Pack Completo',
                'name_en'        => 'Complete Pack',
                'description'    => json_encode(['es' => 'Todo incluido: Web + HD + Fondos + PNG alta calidad para imprimir.', 'en' => 'Everything included: Web + HD + Wallpapers + high quality PNG for printing.', 'fr' => 'Tout inclus : Web + HD + Fonds d\'écran + PNG haute qualité pour imprimer.', 'pt' => 'Tudo incluído: Web + HD + Fundos + PNG alta qualidade para imprimir.', 'it' => 'Tutto incluso: Web + HD + Sfondi + PNG alta qualità per stampare.']),
                'description_es' => 'Todo incluido: Web + HD + Fondos + PNG alta calidad para imprimir.',
                'description_en' => 'Everything included: Web + HD + Wallpapers + high quality PNG for printing.',
                'price_es_cents' => 999,
                'price_en_cents' => 1199,
                'currency_es'    => 'eur',
                'currency_en'    => 'usd',
                'sort_order'     => 4,
                'highlight'      => false,
                'active'         => true,
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['product_key' => $product['product_key']],
                array_merge($product, ['updated_at' => now(), 'created_at' => now()])
            );
        }

        // Productos upsell
        $packCompletoId = DB::table('products')->where('product_key', 'pack_completo')->value('id');

        $upsellProducts = [
            [
                'product_key'           => 'extra_style',
                'name'                  => json_encode(['es' => 'Estilo Extra', 'en' => 'Extra Style', 'fr' => 'Style Supplémentaire', 'pt' => 'Estilo Extra', 'it' => 'Stile Extra']),
                'name_es'               => 'Estilo Extra',
                'name_en'               => 'Extra Style',
                'description'           => json_encode(['es' => 'Un estilo artístico adicional con todos los formatos del Pack Completo.', 'en' => 'An additional artistic style with all Complete Pack formats.', 'fr' => 'Un style artistique supplémentaire avec tous les formats du Pack Complet.', 'pt' => 'Um estilo artístico adicional com todos os formatos do Pack Completo.', 'it' => 'Uno stile artistico aggiuntivo con tutti i formati del Pacchetto Completo.']),
                'description_es'        => 'Un estilo artístico adicional con todos los formatos del Pack Completo.',
                'description_en'        => 'An additional artistic style with all Complete Pack formats.',
                'price_es_cents'        => 299,
                'price_en_cents'        => 349,
                'currency_es'           => 'eur',
                'currency_en'           => 'usd',
                'type'                  => 'digital',
                'sort_order'            => 10,
                'highlight'             => false,
                'active'                => true,
                'is_upsell_addon'       => true,
                'upsell_for_product_id' => $packCompletoId,
                'max_quantity'          => 5,
            ],
            [
                'product_key'           => 'pack_all_styles',
                'name'                  => json_encode(['es' => 'Pack Todos los Estilos', 'en' => 'All Styles Pack', 'fr' => 'Pack Tous les Styles', 'pt' => 'Pack Todos os Estilos', 'it' => 'Pacchetto Tutti gli Stili']),
                'name_es'               => 'Pack Todos los Estilos',
                'name_en'               => 'All Styles Pack',
                'description'           => json_encode(['es' => 'Tu retrato en TODOS los estilos artísticos disponibles. El pack definitivo.', 'en' => 'Your portrait in ALL available artistic styles. The ultimate pack.', 'fr' => 'Votre portrait dans TOUS les styles artistiques disponibles. Le pack ultime.', 'pt' => 'O seu retrato em TODOS os estilos artísticos disponíveis. O pack definitivo.', 'it' => 'Il tuo ritratto in TUTTI gli stili artistici disponibili. Il pacchetto definitivo.']),
                'description_es'        => 'Tu retrato en TODOS los estilos artísticos disponibles. El pack definitivo.',
                'description_en'        => 'Your portrait in ALL available artistic styles. The ultimate pack.',
                'price_es_cents'        => 3499,
                'price_en_cents'        => 3999,
                'currency_es'           => 'eur',
                'currency_en'           => 'usd',
                'type'                  => 'digital',
                'sort_order'            => 5,
                'highlight'             => false,
                'active'                => false, // INACTIVO — futuro
                'is_upsell_addon'       => false,
                'upsell_for_product_id' => null,
                'max_quantity'          => 1,
            ],
        ];

        foreach ($upsellProducts as $product) {
            DB::table('products')->updateOrInsert(
                ['product_key' => $product['product_key']],
                array_merge($product, ['updated_at' => now(), 'created_at' => now()])
            );
        }

        // Relaciones producto ↔ formato
        $productFormats = [
            'web_basic'       => ['web_basic'],
            'web_hd'          => ['web_hd'],
            'wallpaper_pack'  => ['mobile_wallpaper', 'desktop_wallpaper'],
            'pack_completo'   => ['web_basic', 'web_hd', 'mobile_wallpaper', 'desktop_wallpaper', 'print_premium'],
            'extra_style'     => ['web_basic', 'web_hd', 'mobile_wallpaper', 'desktop_wallpaper', 'print_premium'],
            'pack_all_styles' => ['web_basic', 'web_hd', 'mobile_wallpaper', 'desktop_wallpaper', 'print_premium'],
        ];

        foreach ($productFormats as $productKey => $formatKeys) {
            $productId = DB::table('products')->where('product_key', $productKey)->value('id');
            if (!$productId) continue;

            // Limpiar relaciones anteriores de este producto
            DB::table('product_formats')->where('product_id', $productId)->delete();

            foreach ($formatKeys as $formatKey) {
                $formatId = DB::table('formats')->where('format_key', $formatKey)->value('id');
                if (!$formatId) continue;

                DB::table('product_formats')->insert([
                    'product_id' => $productId,
                    'format_id'  => $formatId,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
