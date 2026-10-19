<?php

namespace App\Filament\Widgets;

use App\Models\ProviderLog;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class ProviderPerformanceWidget extends TableWidget
{
    protected static ?string $heading = 'Rendimiento por proveedor (últimos 7 días)';

    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 'full';

    public function getTableRecordKey($record): string
    {
        return $record->provider ?? 'unknown';
    }

    public function table(Table $table): Table
    {
        $from = Carbon::now()->subDays(7);

        return $table
            ->query(
                ProviderLog::query()
                    ->select(
                        'provider',
                        DB::raw('COUNT(*) as total_calls'),
                        DB::raw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_calls"),
                        DB::raw("SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_calls"),
                        DB::raw("SUM(CASE WHEN status = 'timeout' THEN 1 ELSE 0 END) as timeout_calls"),
                        DB::raw("ROUND(AVG(CASE WHEN status = 'success' THEN elapsed_ms END)) as avg_ms"),
                        DB::raw("ROUND(MIN(CASE WHEN status = 'success' THEN elapsed_ms END)) as min_ms"),
                        DB::raw("ROUND(MAX(CASE WHEN status = 'success' THEN elapsed_ms END)) as max_ms"),
                        DB::raw("ROUND(SUM(CASE WHEN status = 'success' THEN cost_usd ELSE 0 END), 4) as total_cost"),
                        DB::raw("ROUND(AVG(CASE WHEN status = 'success' THEN cost_usd END), 4) as avg_cost"),
                    )
                    ->where('created_at', '>=', $from)
                    ->groupBy('provider')
                    ->orderByDesc('total_calls')
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->label('Proveedor')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fal_ai' => 'fal.ai',
                        'openrouter' => 'OpenRouter',
                        'evolink' => 'Evolink',
                        'novita' => 'Novita',
                        default => ucfirst($state),
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('total_calls')
                    ->label('Llamadas')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Éxito')
                    ->alignCenter()
                    ->state(function ($record) {
                        $rate = $record->total_calls > 0
                            ? round(($record->success_calls / $record->total_calls) * 100, 1)
                            : 0;
                        return $rate . '%';
                    })
                    ->color(function ($record) {
                        $rate = $record->total_calls > 0
                            ? ($record->success_calls / $record->total_calls) * 100
                            : 0;
                        return $rate >= 95 ? 'success' : ($rate >= 80 ? 'warning' : 'danger');
                    }),

                Tables\Columns\TextColumn::make('error_calls')
                    ->label('Errores')
                    ->alignCenter()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('timeout_calls')
                    ->label('Timeouts')
                    ->alignCenter()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('avg_ms')
                    ->label('Vel. media')
                    ->alignCenter()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '—';
                        return $state >= 1000
                            ? number_format($state / 1000, 1, ',', '.') . 's'
                            : $state . 'ms';
                    }),

                Tables\Columns\TextColumn::make('speed_range')
                    ->label('Rango')
                    ->alignCenter()
                    ->state(function ($record) {
                        if (!$record->min_ms) return '—';
                        $min = $record->min_ms >= 1000
                            ? number_format($record->min_ms / 1000, 1) . 's'
                            : $record->min_ms . 'ms';
                        $max = $record->max_ms >= 1000
                            ? number_format($record->max_ms / 1000, 1) . 's'
                            : $record->max_ms . 'ms';
                        return $min . ' — ' . $max;
                    }),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Coste total')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => number_format($state ?? 0, 4, ',', '.') . '$'),

                Tables\Columns\TextColumn::make('avg_cost')
                    ->label('Coste/img')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => number_format($state ?? 0, 4, ',', '.') . '$'),
            ]);
    }
}
