<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderLogResource\Pages;
use App\Models\ProviderLog;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderLogResource extends Resource
{
    protected static ?string $model = ProviderLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Logs de IA';

    protected static ?string $modelLabel = 'Log de proveedor';

    protected static ?string $pluralModelLabel = 'Logs de proveedores';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('generation_session_id')
                    ->label('Sesión')
                    ->url(fn (ProviderLog $record): ?string =>
                        $record->generation_session_id
                            ? route('filament.admin.resources.provider-logs.index', ['tableFilters[generation_session_id][value]' => $record->generation_session_id])
                            : null
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost_usd')
                    ->label('Coste USD')
                    ->formatStateUsing(fn ($state): string =>
                        $state !== null ? number_format($state, 4, ',', '.') . '$' : '—'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('elapsed_ms')
                    ->label('Duración')
                    ->formatStateUsing(fn ($state): string =>
                        $state !== null ? number_format($state / 1000, 1, ',', '.') . 's' : '—'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'error'   => 'danger',
                        'timeout' => 'warning',
                        'skipped' => 'gray',
                        default   => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(fn (ProviderLog $record): ?string => $record->error_message)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Proveedor')
                    ->options(fn (): array =>
                        ProviderLog::query()
                            ->distinct()
                            ->pluck('provider', 'provider')
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'success' => 'Success',
                        'error'   => 'Error',
                        'timeout' => 'Timeout',
                        'skipped' => 'Skipped',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Desde ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y'))
                                ->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Hasta ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y'))
                                ->removeField('until');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detalles del log')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('provider')->badge(),
                        Infolists\Components\TextEntry::make('model')->label('Modelo'),
                        Infolists\Components\TextEntry::make('status')->badge()
                            ->color(fn ($state) => match ($state) {
                                'success' => 'success', 'error' => 'danger', 'timeout' => 'warning', default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('cost_usd')->label('Coste USD')
                            ->formatStateUsing(fn ($state) => $state !== null ? '$' . number_format($state, 4) : '—'),
                        Infolists\Components\TextEntry::make('elapsed_ms')->label('Duración')
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 1000, 1) . 's' : '—'),
                        Infolists\Components\TextEntry::make('generation_session_id')->label('Sesión ID'),
                        Infolists\Components\TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                    ]),

                Infolists\Components\Section::make('Error')
                    ->schema([
                        Infolists\Components\TextEntry::make('error_message')->label('Mensaje de error')
                            ->default('Sin errores'),
                    ])
                    ->visible(fn (ProviderLog $record) => !empty($record->error_message)),

                Infolists\Components\Section::make('Request / Response')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('request_summary')->label('Request')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '—')
                            ->prose(),
                        Infolists\Components\TextEntry::make('response_summary')->label('Response')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '—')
                            ->prose(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviderLogs::route('/'),
            'view'  => Pages\ViewProviderLog::route('/{record}'),
        ];
    }
}
