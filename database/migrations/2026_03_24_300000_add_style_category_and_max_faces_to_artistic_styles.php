<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artistic_styles', function (Blueprint $table) {
            $table->string('style_category', 30)->default('artistic')->after('style_key');
            $table->tinyInteger('max_faces')->unsigned()->nullable()->after('style_strength');
            $table->index('style_category');
        });

        // Migrar las categorías que estaban hardcodeadas en la vista
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

    public function down(): void
    {
        Schema::table('artistic_styles', function (Blueprint $table) {
            $table->dropIndex(['style_category']);
            $table->dropColumn(['style_category', 'max_faces']);
        });
    }
};
