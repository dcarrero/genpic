<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Ingresos diarios (últimos 30 días)';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $from = Carbon::now()->subDays(29)->startOfDay();
        $to = Carbon::now()->endOfDay();

        // Ingresos por día
        $revenueByDay = Order::paidReal()
            ->whereBetween('paid_at', [$from, $to])
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(total_cents) as total'))
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Pedidos por día
        $ordersByDay = Order::paidReal()
            ->whereBetween('paid_at', [$from, $to])
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $revenues = [];
        $orders = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('d/m');
            $revenues[] = round(($revenueByDay[$key] ?? 0) / 100, 2);
            $orders[] = $ordersByDay[$key] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos (€)',
                    'data' => $revenues,
                    'borderColor' => '#7A2D3B',
                    'backgroundColor' => 'rgba(122, 45, 59, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Pedidos',
                    'data' => $orders,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => false,
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'ticks' => ['callback' => ''],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                ],
            ],
        ];
    }
}
