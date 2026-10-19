<?php

namespace App\Filament\Widgets;

use App\Models\GenerationSession;
use App\Models\Order;
use App\Models\ProviderLog;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GenerationsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Generaciones hoy
        $generationsToday = GenerationSession::whereDate('created_at', Carbon::today())->count();

        // Tasa de conversión este mes (pedidos pagados / sesiones totales)
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $sessionsThisMonth = GenerationSession::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $paidOrdersThisMonth = Order::paidReal()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->count();

        $conversionRate = $sessionsThisMonth > 0
            ? round(($paidOrdersThisMonth / $sessionsThisMonth) * 100, 1)
            : 0;

        // Coste IA hoy (cost_usd es decimal, no céntimos)
        $aiCostToday = ProviderLog::whereDate('created_at', Carbon::today())->sum('cost_usd');

        return [
            Stat::make('Generaciones hoy', number_format($generationsToday))
                ->color('primary'),

            Stat::make('Tasa de conversión', $conversionRate . '%')
                ->description('Este mes — ' . $paidOrdersThisMonth . '/' . $sessionsThisMonth)
                ->color($conversionRate >= 5 ? 'success' : 'warning'),

            Stat::make('Coste IA hoy', number_format($aiCostToday, 4, ',', '.') . '$')
                ->description($generationsToday . ' generaciones')
                ->color('danger'),
        ];
    }
}
