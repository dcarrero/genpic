<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IpBanResource\Pages;
use App\Models\IpBan;
use App\Services\ContentModeration\IpBanService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IpBanResource extends Resource
{
    protected static ?string $model = IpBan::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationGroup = 'Seguridad';

    protected static ?string $navigationLabel = 'Bans de IP';

    protected static ?string $modelLabel = 'Ban de IP';

    protected static ?string $pluralModelLabel = 'Bans de IP';

    protected static ?int $navigationSort = 2;

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

                Tables\Columns\TextColumn::make('reason')
                    ->label('Razon')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'content_violation' => 'danger',
                        'manual' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('banned_at')
                    ->label('Baneado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y H:i')
                    ->color(fn (IpBan $record): string => $record->isActive() ? 'danger' : 'gray'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('unbanned_at')
                    ->label('Desbaneado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('unbanned_by')
                    ->label('Desbaneado por')
                    ->default('—')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Expirados/Desbaneados')
                    ->queries(
                        true: fn ($query) => $query->where('expires_at', '>', now())->whereNull('unbanned_at'),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->where('expires_at', '<=', now())->orWhereNotNull('unbanned_at');
                        }),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('unban')
                    ->label('Desbanear')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Desbanear IP')
                    ->modalDescription(fn (IpBan $record): string => "Se desbaneara la IP {$record->ip_address}. El ban expiraba el {$record->expires_at->format('d/m/Y H:i')}."
                    )
                    ->visible(fn (IpBan $record): bool => $record->isActive())
                    ->action(function (IpBan $record): void {
                        app(IpBanService::class)->unban(
                            $record->ip_address,
                            auth()->user()->email,
                        );
                        Notification::make()
                            ->title('IP desbaneada')
                            ->body("La IP {$record->ip_address} ha sido desbaneada.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detalles del ban')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address')->label('IP')->copyable(),
                        Infolists\Components\TextEntry::make('reason')->label('Razon')->badge()
                            ->color(fn ($state) => match ($state) {
                                'content_violation' => 'danger', 'manual' => 'warning', default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('banned_at')->label('Baneado')->dateTime('d/m/Y H:i:s'),
                        Infolists\Components\TextEntry::make('expires_at')->label('Expira')->dateTime('d/m/Y H:i:s'),
                        Infolists\Components\TextEntry::make('unbanned_at')->label('Desbaneado')
                            ->dateTime('d/m/Y H:i:s')->placeholder('—'),
                        Infolists\Components\TextEntry::make('unbanned_by')->label('Desbaneado por')->default('—'),
                    ]),

                Infolists\Components\Section::make('Violacion asociada')
                    ->schema([
                        Infolists\Components\TextEntry::make('violation.violation_type')->label('Tipo')->badge(),
                        Infolists\Components\TextEntry::make('violation.provider')->label('Provider')->badge(),
                        Infolists\Components\TextEntry::make('violation.file_hash')->label('Hash archivo')->copyable(),
                        Infolists\Components\TextEntry::make('violation.created_at')->label('Fecha violacion')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2)
                    ->visible(fn (IpBan $record) => $record->violation_id !== null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIpBans::route('/'),
            'view' => Pages\ViewIpBan::route('/{record}'),
        ];
    }
}
