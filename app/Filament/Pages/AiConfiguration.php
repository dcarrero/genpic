<?php

namespace App\Filament\Pages;

use App\Models\ProviderLog;
use Filament\Pages\Page;

class AiConfiguration extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Configuración IA';

    protected static ?string $title = 'Configuración de proveedores IA';

    protected static string $view = 'filament.pages.ai-configuration';

    protected static ?int $navigationSort = 99;

    public function getViewData(): array
    {
        $providers = config('image_providers.providers', []);
        $chain = config('image_providers.chain', []);
        $dailyBudget = config('image_providers.daily_budget_usd', 50);

        // Coste acumulado hoy
        $todayCost = ProviderLog::where('status', 'success')
            ->whereDate('created_at', today())
            ->sum('cost_usd');

        // Stats por proveedor (últimos 7 días)
        $providerStats = [];
        foreach ($providers as $key => $config) {
            $logs = ProviderLog::where('provider', $key)
                ->where('created_at', '>=', now()->subDays(7));

            $providerStats[$key] = [
                'config'        => $config,
                'total_calls'   => (clone $logs)->count(),
                'success_calls' => (clone $logs)->where('status', 'success')->count(),
                'error_calls'   => (clone $logs)->where('status', 'error')->count(),
                'total_cost'    => (clone $logs)->where('status', 'success')->sum('cost_usd'),
                'avg_elapsed'   => (clone $logs)->where('status', 'success')->avg('elapsed_ms'),
            ];
        }

        return [
            'providers'     => $providers,
            'chain'         => $chain,
            'dailyBudget'   => $dailyBudget,
            'todayCost'     => $todayCost,
            'providerStats' => $providerStats,
        ];
    }
}
