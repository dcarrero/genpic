<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando de despliegue inicial para producción.
 *
 * Ejecuta migraciones + seeders + crea admin.
 * Solo necesita ejecutarse una vez en el primer deploy.
 *
 * Uso: php artisan deploy:seed --admin-email=admin@example.com
 */
class DeployInitialSeed extends Command
{
    protected $signature = 'deploy:seed
        {--admin-email= : Email del administrador (requerido)}
        {--fresh : Ejecutar migrate:fresh en vez de migrate (DESTRUYE DATOS)}';

    protected $description = 'Primer deploy: migraciones + seeders (estilos, productos, formatos, testimonios, emails) + usuario admin';

    public function handle(): int
    {
        $adminEmail = $this->option('admin-email');

        if (!$adminEmail) {
            $this->error('Se requiere --admin-email. Ejemplo: php artisan deploy:seed --admin-email=tu@email.com');
            return 1;
        }

        $this->info('=== GenPic — Deploy Inicial ===');
        $this->newLine();

        // 1. Migraciones
        if ($this->option('fresh')) {
            if (!$this->confirm('⚠️  --fresh BORRARÁ TODOS LOS DATOS. ¿Continuar?')) {
                return 1;
            }
            $this->call('migrate:fresh', ['--force' => true]);
        } else {
            $this->call('migrate', ['--force' => true]);
        }

        // 2. Seeders principales
        $this->info('Ejecutando seeders...');

        $seeders = [
            \Database\Seeders\ArtisticStylesSeeder::class   => 'Estilos artísticos',
            \Database\Seeders\StyleCategoriesSeeder::class  => 'Categorías de estilo',
            \Database\Seeders\NewStyleCategoriesSeeder::class => 'Estilos profesionales, temáticos y aventura',
            \Database\Seeders\FormatsAndProductsSeeder::class => 'Formatos y productos',
            \Database\Seeders\TestimonialsSeeder::class      => 'Testimonios',
            \Database\Seeders\EmailSequencesSeeder::class    => 'Secuencias de email',
        ];

        foreach ($seeders as $seeder => $label) {
            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
            $count = match ($seeder) {
                \Database\Seeders\ArtisticStylesSeeder::class    => DB::table('artistic_styles')->count(),
                \Database\Seeders\StyleCategoriesSeeder::class   => DB::table('artistic_styles')->distinct()->count('style_category') . ' categorías',
                \Database\Seeders\NewStyleCategoriesSeeder::class => DB::table('artistic_styles')->count() . ' estilos en total',
                \Database\Seeders\FormatsAndProductsSeeder::class => DB::table('products')->count() . ' productos, ' . DB::table('formats')->count() . ' formatos',
                \Database\Seeders\TestimonialsSeeder::class       => DB::table('testimonials')->count(),
                \Database\Seeders\EmailSequencesSeeder::class     => DB::table('email_sequences')->count(),
            };
            $this->info("  ✓ {$label}: {$count}");
        }

        // 3. Galería de ejemplos (si existe el seeder)
        if (class_exists(\Database\Seeders\GalleryExamplesSeeder::class)) {
            $this->call('db:seed', ['--class' => \Database\Seeders\GalleryExamplesSeeder::class, '--force' => true]);
            $this->info('  ✓ Galería: ' . DB::table('gallery_examples')->count() . ' ejemplos');
        } else {
            $this->warn('  ⚠ GalleryExamplesSeeder no existe — la galería se crea desde admin.');
        }

        // 4. Crear usuario admin
        $user = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'is_admin' => true,
                'locale'   => 'es',
            ]
        );

        if (!$user->is_admin) {
            $user->update(['is_admin' => true]);
        }

        $this->info("  ✓ Admin: {$adminEmail}");

        // 5. Limpiar cache
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        $this->newLine();
        $this->info('=== Deploy inicial completado ===');

        return 0;
    }
}
