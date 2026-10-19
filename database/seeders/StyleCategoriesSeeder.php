<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

/**
 * Asigna la categoría a los estilos de ArtisticStylesSeeder.
 *
 * La migración 2026_03_24_300000 hacía este mismo reparto, pero solo sobre las
 * filas que ya existían en producción; en una instalación limpia la migración
 * corre antes que los seeders y todos los estilos se quedaban en 'artistic'.
 */
class StyleCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categoryMap = [
            // Historic
            'renacimiento' => 'historic', 'siglo_oro' => 'historic', 'victorian' => 'historic',
            'colonial_lat' => 'historic', 'medieval' => 'historic', 'greek_myth' => 'historic',
            'royal' => 'historic', 'egyptian' => 'historic',
            // Artistic
            'posimpresionismo' => 'artistic', 'prerafaelita' => 'artistic', 'art_nouveau' => 'artistic',
            'pop_art' => 'artistic', 'fairy_tale' => 'artistic', 'underwater' => 'artistic',
            'aboriginal' => 'artistic', 'watercolor' => 'artistic', 'pixel_art' => 'artistic',
            'anime' => 'artistic', 'comic_euro' => 'artistic', 'art_deco' => 'artistic',
            // Cultural
            'muralismo' => 'cultural', 'folk_mexicano' => 'cultural', 'ukiyo_e' => 'cultural',
            'habana_vintage' => 'cultural', 'pirates' => 'cultural', 'wild_west' => 'cultural',
            'safari' => 'cultural',
            // Modern
            'sport_70s' => 'modern', 'space' => 'modern', 'military' => 'modern',
            'executive' => 'modern', 'steampunk' => 'modern', 'cyberpunk' => 'modern',
            'superhero' => 'modern', 'disco_80s' => 'modern', 'rockstar' => 'modern', 'wizard' => 'modern',
            'urban_reggaeton' => 'modern',
            // Events
            'christmas' => 'events', 'halloween' => 'events',
            'graduation' => 'events', 'wedding' => 'events',
            // Regional
            'flamenco' => 'regional', 'fallero' => 'regional', 'gallego' => 'regional', 'vasco' => 'regional',
            'gaucho' => 'regional', 'mariachi' => 'regional', 'colombiano' => 'regional',
            'andino' => 'regional', 'brasileno' => 'regional',
            'scottish' => 'regional', 'cowboy_usa' => 'regional', 'indian_royal' => 'regional',
            'african_royal' => 'regional', 'hanbok' => 'regional', 'chinese_dynasty' => 'regional',
            'thai' => 'regional', 'maori' => 'regional',
        ];

        foreach ($categoryMap as $styleKey => $category) {
            DB::table('artistic_styles')
                ->where('style_key', $styleKey)
                ->update(['style_category' => $category]);
        }
    }
}
