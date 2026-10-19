<?php

namespace App\Filament\Widgets;

use App\Models\GenerationSession;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class HourlyActivityWidget extends ChartWidget
{
    protected static ?string $heading = 'Actividad por hora del día (últimos 7 días)';

    protected static ?int $sort = 9;

    protected static ?string $maxHeight = '250px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $from = Carbon::now()->subDays(7);

        // Sesiones por hora
        $sessions = GenerationSession::where('created_at', '>=', $from)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as total'))
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        // Compras por hora
        $orders = Order::paidReal()
            ->where('paid_at', '>=', $from)
            ->select(DB::raw('HOUR(paid_at) as hour'), DB::raw('COUNT(*) as total'))
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        $labels = [];
        $sessionData = [];
        $orderData = [];

        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
            $sessionData[] = $sessions[$h] ?? 0;
            $orderData[] = $orders[$h] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sesiones',
                    'data' => $sessionData,
                    'backgroundColor' => 'rgba(122, 45, 59, 0.6)',
                    'borderColor' => '#7A2D3B',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Compras',
                    'data' => $orderData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.6)',
                    'borderColor' => '#22C55E',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
