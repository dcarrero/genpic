<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersWidget extends TableWidget
{
    protected static ?string $heading = 'Últimos pedidos';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with('user')
                    ->latest()
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8) . '...')
                    ->tooltip(fn (Order $record): string => $record->uuid),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email'),

                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string =>
                        $state > 0
                            ? number_format($state / 100, 2, ',', '.') . '€'
                            : 'Gratis'
                    )
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'       => 'success',
                        'ready'      => 'success',
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'refunded'   => 'gray',
                        'failed'     => 'danger',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}
