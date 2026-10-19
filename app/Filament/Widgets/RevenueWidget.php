<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Hoy (excluye pedidos 0€ = regalos)
        $todayQuery = Order::paidReal()
            ->whereDate('paid_at', Carbon::today());
        $todayRevenue = $todayQuery->sum('total_cents');
        $todayCount = $todayQuery->count();

        // Esta semana
        $weekQuery = Order::paidReal()
            ->whereBetween('paid_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        $weekRevenue = $weekQuery->sum('total_cents');
        $weekCount = $weekQuery->count();

        // Este mes
        $monthQuery = Order::paidReal()
            ->whereBetween('paid_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        $monthRevenue = $monthQuery->sum('total_cents');
        $monthCount = $monthQuery->count();

        return [
            Stat::make('Ingresos hoy', number_format($todayRevenue / 100, 2, ',', '.') . '€')
                ->description($todayCount . ' ' . ($todayCount === 1 ? 'pedido' : 'pedidos'))
                ->color('success'),

            Stat::make('Ingresos esta semana', number_format($weekRevenue / 100, 2, ',', '.') . '€')
                ->description($weekCount . ' ' . ($weekCount === 1 ? 'pedido' : 'pedidos'))
                ->color('success'),

            Stat::make('Ingresos este mes', number_format($monthRevenue / 100, 2, ',', '.') . '€')
                ->description($monthCount . ' ' . ($monthCount === 1 ? 'pedido' : 'pedidos'))
                ->color('success'),
        ];
    }
}
