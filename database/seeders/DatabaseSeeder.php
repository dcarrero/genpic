<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ArtisticStylesSeeder::class,
            StyleCategoriesSeeder::class,
            NewStyleCategoriesSeeder::class,
            FormatsAndProductsSeeder::class,
            TestimonialsSeeder::class,
            EmailSequencesSeeder::class,
            GalleryExamplesSeeder::class,
            SiteSettingsSeeder::class,
        ]);
    }
}
