# Plan 009: Visor privado de imágenes en el admin — original y generadas en el detalle de GenerationSession

## Status

- **Priority**: P2
- **Effort**: M
- **Risk**: LOW–MED (toca el panel admin en producción; nada del funnel de usuario)
- **Depends on**: none (suite verde en `5d78cbd`)
- **Category**: feature (petición del operador, 2026-06-12)
- **Planned at**: commit `5d78cbd`, 2026-06-12

## Why this matters

Al revisar una sesión en el admin (`/admin/generation-sessions/{uuid}`) el operador no puede VER las imágenes — ni la foto original subida ni los retratos generados — solo rutas de texto. Para moderación y soporte necesita verlas. Requisito de seguridad explícito del operador: **las URLs no deben poder filtrarse** — regla de negocio nº 4 del proyecto: nunca URLs públicas a Wasabi. Por tanto NO se usan URLs firmadas/temporales (copiables y válidas para cualquiera durante su TTL): las imágenes se sirven **por streaming a través de la app**, protegidas por la sesión de Filament + la allowlist de IP del admin. Sin sesión de admin válida, la URL no devuelve nada.

## Current state

- `version-es/app/Models/GenerationSession.php:18` — fillable incluye `original_photo_path`, `original_filename` (+ `purged_at` en el modelo; verificarlo). Route key del resource es el uuid.
- `version-es/app/Models/Generation.php:18` — fillable incluye `raw_image_path`, `watermarked_image_path`.
- `version-es/app/Services/StorageService.php` — métodos disponibles: `disk(): string` (línea 13), `exists(string $path): bool` (78), `size(string $path): int` (86). El disco real es Wasabi en producción, `local` en testing.
- `version-es/app/Filament/Resources/GenerationSessionResource.php` — el Infolist del detalle (líneas ~130–215) ya muestra `latestGeneration.*` como TextEntry. La relación con generaciones existe (`latestGeneration`; comprobar si hay también `generations` hasMany).
- `version-es/app/Providers/Filament/AdminPanelProvider.php` — el panel monta `->middleware([...])` con `RestrictAdminByIp::class` (línea 63) y `->authMiddleware([...])` (línea 65, típicamente `Filament\Http\Middleware\Authenticate`). El path del panel es `admin`.
- `version-es/routes/web.php` — las rutas custom van fuera del panel; los webhooks ~línea 36.
- **Regla del repo**: precios en céntimos, comentarios en español, NUNCA URLs públicas a Wasabi. Los ficheros pueden estar **purgados** (purga diaria, TTL 14 días) — `purged_at` en la sesión; un path puede existir en BD y no en storage.
- ANTIPATRÓN conocido del repo (memoria del proyecto): NO usar dot notation en columnas de TABLA Filament para campos JSON cast — aquí no aplica (Infolist), pero no introducirlo.

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Tests | `APP_KEY="base64:$(openssl rand -base64 32)" php artisan test` | exit 0 (baseline 127 tests) |
| Test nuevo | `... php artisan test --filter=AdminMediaTest` | pasa |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |

## Scope

**In scope**:
- `version-es/app/Http/Controllers/Admin/AdminMediaController.php` (crear)
- `version-es/routes/web.php` (añadir el grupo de rutas admin-media)
- `version-es/app/Filament/Resources/GenerationSessionResource.php` (añadir sección de imágenes al Infolist)
- `version-es/tests/Feature/AdminMediaTest.php` (crear)

**Out of scope** (NO tocar):
- `SecureImageController` y el flujo de imágenes del usuario final (PIN/signed URLs) — es otro camino con otra semántica.
- `StorageService::temporaryUrl()` — NO usarlo aquí (URL firmada = filtrable; el requisito lo prohíbe).
- `AdminPanelProvider` — no cambiar el panel; las rutas nuevas reutilizan sus middleware por referencia.
- Cualquier otra página de Filament.

## Git workflow

- Branch: `advisor/009-admin-media`
- Commit estilo repo: `feat: visor privado de imágenes en admin — original y generadas en detalle de sesión`
- No push salvo instrucción del operador.

## Steps

### Step 1: Controller de streaming privado

Crear `version-es/app/Http/Controllers/Admin/AdminMediaController.php` con dos acciones:

```php
// GET /admin/media/session/{session:uuid}/original
public function original(GenerationSession $session)
{
    return $this->stream($session->original_photo_path);
}

// GET /admin/media/generation/{generation}/{kind}  — kind: raw|watermarked
public function generation(Generation $generation, string $kind)
{
    abort_unless(in_array($kind, ['raw', 'watermarked'], true), 404);
    $path = $kind === 'raw' ? $generation->raw_image_path : $generation->watermarked_image_path;

    return $this->stream($path);
}

private function stream(?string $path)
{
    $storage = app(StorageService::class);
    if (! $path || ! $storage->exists($path)) {
        abort(404, 'Archivo no disponible (puede haber sido purgado)');
    }

    // Streaming a través de la app — la URL no sirve sin sesión admin + IP autorizada
    return Storage::disk($storage->disk())->response($path, null, [
        'Cache-Control' => 'private, no-store',
        'X-Robots-Tag' => 'noindex, nofollow',
        'Content-Disposition' => 'inline',
    ]);
}
```

(Ajustar imports; si `Storage::response()` con el driver S3 diera problemas, alternativa equivalente: `response()->stream(fn () => fpassthru(Storage::disk(...)->readStream($path)), 200, [...headers + Content-Type por extensión...])`.)

**Verify**: `php -l app/Http/Controllers/Admin/AdminMediaController.php` → sin errores

### Step 2: Rutas con la MISMA protección que el panel

En `version-es/routes/web.php`, añadir (fuera de los grupos de locale, junto a otras rutas no localizadas):

```php
// Visor de imágenes del admin — streaming privado, misma protección que Filament:
// sesión de admin (guard del panel) + allowlist de IP. Sin URLs firmadas (no filtrables).
Route::middleware([
    'web',
    \Filament\Http\Middleware\Authenticate::class,
    \App\Http\Middleware\RestrictAdminByIp::class,
])->prefix('admin/media')->name('admin.media.')->group(function () {
    Route::get('/session/{session:uuid}/original', [\App\Http\Controllers\Admin\AdminMediaController::class, 'original'])->name('original');
    Route::get('/generation/{generation}/{kind}', [\App\Http\Controllers\Admin\AdminMediaController::class, 'generation'])->name('generation')->whereIn('kind', ['raw', 'watermarked']);
});
```

Nota: `Filament\Http\Middleware\Authenticate` redirige al login del panel si no hay sesión — comprobar en `AdminPanelProvider::authMiddleware()` la clase exacta que usa el panel y usar ESA. Si el panel usa un guard no-default, replicarlo.

**Verify**: `php artisan route:list | grep admin.media` → 2 rutas con los middleware correctos

### Step 3: Sección de imágenes en el Infolist

En `GenerationSessionResource` (método del infolist), añadir una sección "Imágenes" con vistas previas + enlace a tamaño completo en pestaña nueva. Usar `Infolists\Components\ImageEntry` apuntando a las rutas nuevas (el `<img>` del navegador del admin envía la cookie de sesión, así que carga; para cualquier otro, 403/redirect):

```php
Infolists\Components\Section::make('Imágenes')
    ->schema([
        Infolists\Components\ImageEntry::make('original_photo_path')
            ->label('Foto original')
            ->state(fn ($record) => $record->original_photo_path
                ? route('admin.media.original', $record) : null)
            ->height(200)
            ->url(fn ($record) => $record->original_photo_path
                ? route('admin.media.original', $record) : null, shouldOpenInNewTab: true)
            ->placeholder('No disponible / purgada'),
        Infolists\Components\ImageEntry::make('latestGeneration.raw_image_path')
            ->label('Generada (raw)')
            ->state(fn ($record) => $record->latestGeneration?->raw_image_path
                ? route('admin.media.generation', [$record->latestGeneration, 'raw']) : null)
            ->height(200)
            ->url(fn ($record) => $record->latestGeneration?->raw_image_path
                ? route('admin.media.generation', [$record->latestGeneration, 'raw']) : null, shouldOpenInNewTab: true)
            ->placeholder('No disponible / purgada'),
        Infolists\Components\ImageEntry::make('latestGeneration.watermarked_image_path')
            ->label('Con marca de agua')
            ->state(fn ($record) => $record->latestGeneration?->watermarked_image_path
                ? route('admin.media.generation', [$record->latestGeneration, 'watermarked']) : null)
            ->height(200)
            ->url(fn ($record) => $record->latestGeneration?->watermarked_image_path
                ? route('admin.media.generation', [$record->latestGeneration, 'watermarked']) : null, shouldOpenInNewTab: true)
            ->placeholder('No disponible / purgada'),
    ])
    ->columns(3),
```

Colocarla como primera sección del infolist (lo más útil al abrir el detalle). Ajustar la sintaxis exacta de `ImageEntry`/`url()` a Filament v3.3 (consultar otra Section existente del mismo fichero como patrón).

**Verify**: `php artisan route:list > /dev/null && php -l app/Filament/Resources/GenerationSessionResource.php` → OK

### Step 4: Tests

Crear `version-es/tests/Feature/AdminMediaTest.php` (RefreshDatabase + `Storage::fake('local')`). El TestCase global ya hace `withoutVite()` y `fakeRedis()`. Configurar en cada test `config(['genpic.admin_allowed_ips' => []])` (lista vacía = no restringe, mirar el middleware) o incluir la IP de test, y `config(['genpic.admin_emails' => ['admin@test.com']])` para `canAccessPanel`. Casos:

1. **Sin autenticar** → GET ruta original → redirect al login (302) o 403 — NUNCA la imagen.
2. **Autenticado como admin** (User factory con email en `genpic.admin_emails`, `$this->actingAs($admin)`) + fichero existente en Storage fake → 200 y `Content-Type` de imagen + `Cache-Control: private, no-store`.
3. **Fichero purgado** (path en BD, fichero no existe en disco) → 404.
4. **IP no autorizada** (config `admin_allowed_ips` con una IP distinta a la del test) → 403 aunque esté autenticado.
5. **kind inválido** → 404.

**Verify**: `APP_KEY=... php artisan test --filter=AdminMediaTest` → 5 tests pasan

### Step 5: Suite completa + lint

**Verify**: `php artisan test` → exit 0 (≥132 tests); `php vendor/bin/pint --dirty` → exit 0

## Test plan

Cubierto en Step 4 — la propiedad de seguridad clave es el caso 1 (la URL sin sesión no sirve NADA) y el caso 4 (IP). Patrón: tests Feature existentes.

## Done criteria

- [ ] `php artisan route:list | grep -c admin.media` → 2
- [ ] `grep -c "temporaryUrl" version-es/app/Http/Controllers/Admin/AdminMediaController.php` → 0 (sin URLs firmadas)
- [ ] `php artisan test --filter=AdminMediaTest` → exit 0 (5 tests)
- [ ] `php artisan test` → exit 0
- [ ] `git status` → sin cambios fuera del scope
- [ ] Fila actualizada en `plans/README.md`

## STOP conditions

- `AdminPanelProvider::authMiddleware()` usa una clase de autenticación distinta a `Filament\Http\Middleware\Authenticate` y no está claro cómo replicarla en rutas externas — reportar la clase encontrada.
- `Storage::disk(...)->response()` no existe o falla con el driver del test Y la alternativa con `readStream` también — reportar.
- `ImageEntry` de Filament v3.3 no admite `state()` con URL externa — usar `ViewEntry` con una blade mínima inline en el resource… si eso tampoco encaja, STOP y reportar opciones.
- El modelo `Generation` no tiene route model binding por id accesible o la relación `latestGeneration` no existe — reportar.

## Maintenance notes

- Las imágenes se purgan a los 14 días: el 404 "purgada" es comportamiento esperado, no bug.
- Si en el futuro se quiere miniaturas más ligeras (el raw puede ser pesado), añadir un parámetro `?w=` con redimensionado al vuelo — fuera de alcance hoy.
- Revisor: comprobar que NINGUNA ruta nueva queda fuera del grupo con `Authenticate` + `RestrictAdminByIp`, y que no se usa `temporaryUrl`.
- El ancho de banda pasa por el origin (streaming) — correcto para uso admin; no usar este patrón para tráfico de usuarios finales.
