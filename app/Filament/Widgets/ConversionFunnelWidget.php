<?php

namespace App\Filament\Widgets;

use App\Models\GenerationSession;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConversionFunnelWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Funnel de conversión (últimos 30 días)';

    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $from = Carbon::now()->subDays(30);

        $totalSessions = GenerationSession::where('created_at', '>=', $from)->count();

        $withEmail = GenerationSession::where('created_at', '>=', $from)
            ->whereNotNull('email_captured_at')
            ->count();

        $withGeneration = GenerationSession::where('created_at', '>=', $from)
            ->whereIn('status', ['watermarked', 'paid'])
            ->count();

        $paidOrders = Order::paidReal()
            ->where('paid_at', '>=', $from)
            ->count();

        $uploadToEmailRate = $totalSessions > 0
            ? round(($withEmail / $totalSessions) * 100, 1) : 0;

        $emailToPaidRate = $withEmail > 0
            ? round(($paidOrders / $withEmail) * 100, 1) : 0;

        $overallRate = $totalSessions > 0
            ? round(($paidOrders / $totalSessions) * 100, 1) : 0;

        return [
            Stat::make('Sesiones → Email', $uploadToEmailRate . '%')
                ->description($withEmail . ' de ' . $totalSessions . ' sesiones')
                ->color($uploadToEmailRate >= 50 ? 'success' : 'warning')
                ->chart($this->getDailyTrend($from, 'sessions')),

            Stat::make('Email → Compra', $emailToPaidRate . '%')
                ->description($paidOrders . ' de ' . $withEmail . ' con email')
                ->color($emailToPaidRate >= 10 ? 'success' : 'warning')
                ->chart($this->getDailyTrend($from, 'orders')),

            Stat::make('Conversión total', $overallRate . '%')
                ->description($paidOrders . ' compras de ' . $totalSessions . ' sesiones')
                ->color($overallRate >= 5 ? 'success' : ($overallRate >= 2 ? 'warning' : 'danger'))
                ->chart($this->getDailyTrend($from, 'conversion')),
        ];
    }

    private function getDailyTrend(Carbon $from, string $type): array
    {
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();

            if ($type === 'sessions') {
                $days->push(GenerationSession::whereDate('created_at', $date)->count());
            } elseif ($type === 'orders') {
                $days->push(Order::paidReal()->whereDate('paid_at', $date)->count());
            } else {
                $sessions = GenerationSession::whereDate('created_at', $date)->count();
                $orders = Order::paidReal()->whereDate('paid_at', $date)->count();
                $days->push($sessions > 0 ? round(($orders / $sessions) * 100) : 0);
            }
        }

        return $days->toArray();
    }
}
