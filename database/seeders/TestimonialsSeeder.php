<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestimonialsSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'quote' => json_encode([
                    'es' => 'Subí la foto de la boda de mis padres, la de los 80. Mi madre se echó a llorar cuando vio el cuadro. Dice que lo quiere enmarcar en el salón.',
                    'en' => 'I uploaded our wedding photo from the 80s. My mum burst into tears when she saw the painting. She wants it framed in the living room.',
                ]),
                'author' => json_encode(['es' => 'Laura G. · Madrid', 'en' => 'Sarah T. · London']),
                'tag' => json_encode(['es' => 'Foto de familia', 'en' => 'Family photo']),
                'rating' => 5, 'sort_order' => 1, 'active' => true,
            ],
            [
                'quote' => json_encode([
                    'es' => 'Lo pedí como regalo de aniversario con una foto nuestra. La calidad es brutal, lo imprimimos en grande y parece un cuadro de verdad. El mejor regalo que he hecho.',
                    'en' => 'Ordered it as an anniversary gift with a photo of us. The quality is incredible — we printed it large and it looks like a real painting. Best gift I\'ve ever given.',
                ]),
                'author' => json_encode(['es' => 'Andrés M. · Barcelona', 'en' => 'James & Kate M. · Edinburgh']),
                'tag' => json_encode(['es' => 'Regalo pareja', 'en' => 'Couple gift']),
                'rating' => 5, 'sort_order' => 2, 'active' => true,
            ],
            [
                'quote' => json_encode([
                    'es' => 'Hicimos uno con la foto del grupo de amigas de la uni. Estilo muralismo. Está colgado en la oficina y todo el mundo pregunta dónde lo compramos.',
                    'en' => 'Made one with our uni friend group photo. Muralism style. It\'s hanging in the office and everyone asks where we got it.',
                ]),
                'author' => json_encode(['es' => 'Valeria T. · Ciudad de México', 'en' => 'Tyler B. · Austin, TX']),
                'tag' => json_encode(['es' => 'Foto de amigos', 'en' => 'Friend group']),
                'rating' => 5, 'sort_order' => 3, 'active' => true,
            ],
            [
                'quote' => json_encode([
                    'es' => 'Le regalé a mi abuela un retrato estilo Renacimiento con la foto de toda la familia en Navidad. No paraba de enseñarlo a las vecinas 😂',
                    'en' => 'Gave my grandma a Renaissance portrait of the whole family at Christmas. She wouldn\'t stop showing it to the neighbours 😂',
                ]),
                'author' => json_encode(['es' => 'Javi P. · Valencia', 'en' => 'Priya K. · Sydney']),
                'tag' => json_encode(['es' => 'Foto familiar', 'en' => 'Family portrait']),
                'rating' => 5, 'sort_order' => 4, 'active' => true,
            ],
            [
                'quote' => json_encode([
                    'es' => 'Tomé el pack completo con una foto mía y lo mandé a imprimir en una copistería. Quedó perfecto. La resolución es más que suficiente para un 40×60.',
                    'en' => 'Got the Complete Pack and had it printed at 16×24. The resolution is more than enough. Looks like a real painting.',
                ]),
                'author' => json_encode(['es' => 'Susana L. · Zaragoza', 'en' => 'Christine M. · Toronto']),
                'tag' => json_encode(['es' => 'Retrato individual', 'en' => 'Print quality']),
                'rating' => 5, 'sort_order' => 5, 'active' => true,
            ],
            [
                'quote' => json_encode([
                    'es' => 'Hice la foto de mis hijos con el estilo japonés Ukiyo-e. Fliparon cuando lo vieron. Ya me han pedido que haga uno con los abuelos.',
                    'en' => 'Did our kids in the Japanese Ukiyo-e style. They absolutely loved it. Already asked me to make one with the grandparents too.',
                ]),
                'author' => json_encode(['es' => 'Carmen R. · Sevilla', 'en' => 'Emma R. · Manchester']),
                'tag' => json_encode(['es' => 'Foto de niños', 'en' => 'Kids portrait']),
                'rating' => 5, 'sort_order' => 6, 'active' => true,
            ],
        ];

        foreach ($testimonials as $t) {
            DB::table('testimonials')->updateOrInsert(
                ['sort_order' => $t['sort_order']],
                array_merge($t, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }
}
