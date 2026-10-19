<?php

namespace App\Filament\Widgets;

use App\Models\GenerationSession;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PopularStylesWidget extends ChartWidget
{
    protected static ?string $heading = 'Estilos más populares (este mes)';

    protected static ?int $sort = 10; // Al lado de HourlyActivity (sort=9)

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $data = GenerationSession::query()
            ->select('style_key', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->whereNotNull('style_key')
            ->groupBy('style_key')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Sesiones',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#7A2D3B',
                    'borderColor' => '#7A2D3B',
                ],
            ],
            'labels' => $data->pluck('style_key')->toArray(),
        ];
    }
}
