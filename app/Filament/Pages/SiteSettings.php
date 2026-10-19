<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\CachedQueries;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?string $navigationLabel = 'Configuración Web';
    protected static ?string $title = 'Configuración del Sitio';
    protected static string $view = 'filament.pages.site-settings';
    protected static ?int $navigationSort = 98;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'google_analytics_id' => SiteSetting::get('google_analytics_id'),
            'head_scripts'        => SiteSetting::get('head_scripts'),
            'body_scripts'        => SiteSetting::get('body_scripts'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Google Analytics')
                    ->description('GA4 se carga en modo cookieless (sin consentimiento). Si el usuario acepta cookies analíticas, se activan cookies completas para visitantes recurrentes.')
                    ->schema([
                        TextInput::make('google_analytics_id')
                            ->label('Measurement ID (GA4)')
                            ->placeholder('G-XXXXXXXXXX')
                            ->helperText(fn () => match (SiteSetting::source('google_analytics_id')) {
                                'db'  => 'Configurado en base de datos (admin).',
                                'env' => 'Leído desde .env (GOOGLE_ANALYTICS_ID). Guarda aquí para sobreescribir.',
                                default => 'No configurado. Ejemplo: G-XXXXXXXXXX',
                            }),
                    ]),

                Section::make('Scripts adicionales')
                    ->description('Códigos extra (Meta Pixel, Hotjar, etc.). Solo se cargan si el usuario acepta cookies analíticas en el banner.')
                    ->schema([
                        Textarea::make('head_scripts')
                            ->label('Scripts en <head>')
                            ->placeholder('<!-- Meta Pixel, Hotjar, etc. -->')
                            ->helperText('Se inyecta en <head> solo con consentimiento de cookies analíticas.')
                            ->rows(4),
                        Textarea::make('body_scripts')
                            ->label('Scripts antes de </body>')
                            ->placeholder('<!-- Scripts de cierre -->')
                            ->helperText('Se inyecta antes de </body> solo con consentimiento de cookies analíticas.')
                            ->rows(4),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::set('google_analytics_id', $data['google_analytics_id'] ?? null, 'analytics');
        SiteSetting::set('head_scripts', $data['head_scripts'] ?? null, 'analytics');
        SiteSetting::set('body_scripts', $data['body_scripts'] ?? null, 'analytics');

        // Limpiar cache de páginas al cambiar settings
        $this->flushPageCache();

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('flush_cache')
                ->label('Borrar toda la cache')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Borrar cache')
                ->modalDescription('Se borrará la cache de páginas públicas, consultas de estilos, productos y galería. Las páginas se regenerarán en la siguiente visita.')
                ->action(function () {
                    $this->flushPageCache();
                    CachedQueries::flushStyles();
                    CachedQueries::flushProducts();

                    Notification::make()
                        ->title('Cache borrada')
                        ->body('Páginas públicas y consultas cacheadas eliminadas.')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function flushPageCache(): void
    {
        // Borrar todas las claves page_cache:* de Redis
        $prefix = config('cache.prefix', '') . ':page_cache:';
        $redis = Cache::getStore()->getRedis();
        $keys = $redis->keys("{$prefix}*");
        if ($keys) {
            $redis->del($keys);
        }
    }
}
