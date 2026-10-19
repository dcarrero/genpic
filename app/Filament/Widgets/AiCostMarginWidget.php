<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\ProviderLog;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiCostMarginWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Coste IA vs Ingresos';

    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        // Coste IA este mes (USD)
        $aiCostMonth = ProviderLog::where('status', 'success')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('cost_usd');

        // Ingresos este mes (EUR cents → EUR)
        $revenueMonth = Order::paidReal()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('total_cents') / 100;

        // Convertir coste IA a EUR aproximado (1 USD ≈ 0.92 EUR)
        $aiCostEur = $aiCostMonth * 0.92;

        // Margen bruto IA
        $marginPercent = $revenueMonth > 0
            ? round((($revenueMonth - $aiCostEur) / $revenueMonth) * 100, 1)
            : 100;

        // Coste medio por generación
        $genCount = ProviderLog::where('status', 'success')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();
        $avgCost = $genCount > 0 ? $aiCostMonth / $genCount : 0;

        // Presupuesto diario
        $dailyBudget = config('image_providers.daily_budget_usd', 50);
        $todayCost = ProviderLog::where('status', 'success')
            ->whereDate('created_at', today())
            ->sum('cost_usd');
        $budgetUsedPercent = $dailyBudget > 0
            ? round(($todayCost / $dailyBudget) * 100, 1) : 0;

        // Proyección fin de mes
        $daysElapsed = max(1, Carbon::now()->day);
        $daysInMonth = Carbon::now()->daysInMonth;
        $projectedRevenue = ($revenueMonth / $daysElapsed) * $daysInMonth;
        $projectedCost = ($aiCostEur / $daysElapsed) * $daysInMonth;

        return [
            Stat::make('Margen bruto IA', $marginPercent . '%')
                ->description(
                    'Ingresos ' . number_format($revenueMonth, 2, ',', '.') . '€ — Coste IA ' .
                    number_format($aiCostEur, 2, ',', '.') . '€'
                )
                ->color($marginPercent >= 80 ? 'success' : ($marginPercent >= 60 ? 'warning' : 'danger'))
                ->chart($this->getMarginTrend()),

            Stat::make('Coste medio/generación', number_format($avgCost, 4, ',', '.') . '$')
                ->description($genCount . ' generaciones este mes')
                ->color('info'),

            Stat::make('Presupuesto diario', $budgetUsedPercent . '% usado')
                ->description(
                    number_format($todayCost, 2, ',', '.') . '$ de ' .
                    number_format($dailyBudget, 0) . '$ máx'
                )
                ->color($budgetUsedPercent >= 90 ? 'danger' : ($budgetUsedPercent >= 70 ? 'warning' : 'success')),
        ];
    }

    private function getMarginTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();

            $revenue = Order::paidReal()
                ->whereDate('paid_at', $date)
                ->sum('total_cents') / 100;

            $cost = ProviderLog::where('status', 'success')
                ->whereDate('created_at', $date)
                ->sum('cost_usd') * 0.92;

            $trend[] = $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100) : 100;
        }

        return $trend;
    }
}
