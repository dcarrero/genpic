<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * design.md gestiona el proyecto: toda vista debe declarar su adhesion al
 * sistema. Sin esto la cobertura se erosiona vista a vista sin que nadie lo note.
 */
class DesignSystemStampTest extends TestCase
{
    /** Vistas fuera del sistema, con motivo. */
    private const EXEMPT = [
        // Scaffolding de Laravel: no enrutado, cero referencias en routes/ y app/.
        'welcome.blade.php',
    ];

    private function views(): array
    {
        $out = [];
        $base = resource_path('views');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($it as $f) {
            if (! $f->isFile() || ! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }
            $rel = str_replace($base . '/', '', $f->getPathname());
            // Los emails tienen su propio layout; filament es de terceros;
            // los parciales heredan el stamp de quien los incluye.
            if (str_starts_with($rel, 'emails/') || str_starts_with($rel, 'filament/')) {
                continue;
            }
            if (str_starts_with($f->getFilename(), '_')) {
                continue;
            }
            $out[$rel] = $f->getPathname();
        }

        return $out;
    }

    public function test_every_view_declares_the_design_system(): void
    {
        $missing = [];
        foreach ($this->views() as $rel => $path) {
            if (in_array($rel, self::EXEMPT, true)) {
                continue;
            }
            if (! str_contains(file_get_contents($path), 'Hallmark ·')) {
                $missing[] = $rel;
            }
        }

        $this->assertSame([], $missing, "Vistas sin stamp de design.md:\n  " . implode("\n  ", $missing));
    }

    /** Cada pagina declara su familia; el sistema define tres. */
    public function test_page_stamps_name_a_known_family(): void
    {
        $bad = [];
        foreach ($this->views() as $rel => $path) {
            $src = file_get_contents($path);
            if (! str_contains($src, 'Hallmark ·')) {
                continue;
            }
            // Componentes y chrome estampan distinto: no llevan familia.
            if (str_contains($src, 'Hallmark · component:') || str_contains($src, 'Hallmark · chrome:')) {
                continue;
            }
            if (! preg_match('/family: (marketing|app|contenido)\b/', $src)) {
                $bad[] = $rel;
            }
        }

        $this->assertSame([], $bad, "Stamps sin familia valida:\n  " . implode("\n  ", $bad));
    }

    /** design.md existe y declara las tres familias. */
    public function test_design_md_declares_the_three_families(): void
    {
        $p = base_path('docs/design.md');
        $this->assertFileExists($p, 'docs/design.md desaparecio del repo');

        $md = file_get_contents($p);
        foreach (['Marketing', 'App', 'Contenido'] as $family) {
            $this->assertStringContainsString($family, $md, "design.md no declara la familia {$family}");
        }
    }
}
