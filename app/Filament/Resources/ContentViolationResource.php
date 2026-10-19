<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentViolationResource\Pages;
use App\Models\ContentViolation;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContentViolationResource extends Resource
{
    protected static ?string $model = ContentViolation::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Seguridad';

    protected static ?string $navigationLabel = 'Violaciones';

    protected static ?string $modelLabel = 'Violacion de contenido';

    protected static ?string $pluralModelLabel = 'Violaciones de contenido';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->default('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('violation_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nudity'         => 'danger',
                        'violence'       => 'danger',
                        'weapons'        => 'warning',
                        'drugs'          => 'warning',
                        'hate'           => 'danger',
                        'content_policy' => 'warning',
                        default          => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('violation_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state): string =>
                        $state !== null ? number_format($state * 100, 1) . '%' : '—'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_hash')
                    ->label('Hash')
                    ->limit(16)
                    ->tooltip(fn (ContentViolation $record): string => $record->file_hash)
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('violation_type')
                    ->label('Tipo')
                    ->options([
                        'nudity'         => 'Nudity',
                        'weapons'        => 'Weapons',
                        'drugs'          => 'Drugs',
                        'violence'       => 'Violence',
                        'hate'           => 'Hate',
                        'content_policy' => 'Content Policy (IA)',
                        'other'          => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('provider')
                    ->label('Provider')
                    ->options(fn (): array =>
                        ContentViolation::query()
                            ->distinct()
                            ->pluck('provider', 'provider')
                            ->toArray()
                    ),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
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
                Infolists\Components\Section::make('Detalles de la violacion')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('violation_type')->label('Tipo')->badge()
                            ->color(fn ($state) => match ($state) {
                                'nudity', 'violence', 'hate' => 'danger',
                                'weapons', 'drugs', 'content_policy' => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('violation_score')->label('Score')
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state * 100, 1) . '%' : '—'),
                        Infolists\Components\TextEntry::make('provider')->badge(),
                        Infolists\Components\TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                    ]),

                Infolists\Components\Section::make('Informacion del usuario')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address')->label('IP')->copyable(),
                        Infolists\Components\TextEntry::make('email')->default('No proporcionado'),
                        Infolists\Components\TextEntry::make('user_agent')->label('User Agent')
                            ->default('Desconocido')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('locale')->label('Idioma')->default('—'),
                    ]),

                Infolists\Components\Section::make('Archivo')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('file_hash')->label('SHA-256')->copyable()->columnSpanFull(),
                        Infolists\Components\TextEntry::make('file_name')->label('Nombre archivo')->default('—'),
                        Infolists\Components\TextEntry::make('file_size_bytes')->label('Tamano')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 0) . ' KB' : '—'),
                    ]),

                Infolists\Components\Section::make('Respuesta del provider')
                    ->schema([
                        Infolists\Components\TextEntry::make('provider_response')
                            ->label('JSON')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—')
                            ->prose(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentViolations::route('/'),
            'view'  => Pages\ViewContentViolation::route('/{record}'),
        ];
    }
}
