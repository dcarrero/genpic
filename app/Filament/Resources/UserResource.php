<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Usuarios';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

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

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('admin_env')
                    ->label('Admin')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool =>
                        in_array($record->email, config('genpic.admin_emails', []), true)
                    )
                    ->tooltip('Definido en ADMIN_EMAILS (.env)'),

                Tables\Columns\TextColumn::make('locale')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('import_source')
                    ->label('Origen')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('country_code')
                    ->label('País')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('marketing_opt_out_at')
                    ->label('Mktg')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => !$record->hasOptedOutOfMarketing())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Pedidos')
                    ->sortable(),

                Tables\Columns\TextColumn::make('generation_sessions_count')
                    ->counts('generationSessions')
                    ->label('Sesiones')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Última visita')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('admin')
                    ->label('Solo admins')
                    ->query(fn ($query) => $query->whereIn(
                        'email',
                        config('genpic.admin_emails', [])
                    )),

                Tables\Filters\SelectFilter::make('locale')
                    ->label('Idioma')
                    ->options([
                        'es' => 'Español',
                        'en' => 'English',
                        'fr' => 'Français',
                        'pt' => 'Português',
                        'it' => 'Italiano',
                    ]),

                Tables\Filters\SelectFilter::make('import_source')
                    ->label('Origen importación')
                    ->options(fn () => User::whereNotNull('import_source')
                        ->distinct()
                        ->pluck('import_source', 'import_source')
                        ->toArray()
                    ),

                Tables\Filters\Filter::make('imported')
                    ->label('Solo importados')
                    ->query(fn ($query) => $query->whereNotNull('import_source')),

                Tables\Filters\Filter::make('unsubscribed')
                    ->label('Desuscritos marketing')
                    ->query(fn ($query) => $query->whereNotNull('marketing_opt_out_at')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar usuario')
                    ->modalDescription(fn (User $record) => "Se anonimizará {$record->email}. Las orders y sesiones se conservan (regla de negocio).")
                    ->visible(fn (User $record): bool =>
                        !in_array($record->email, config('genpic.admin_emails', []), true)
                    )
                    ->action(function (User $record) {
                        $email = $record->email;

                        // Nunca borrar orders (regla de negocio crítica)
                        // Desvincular orders pendientes sin pago del usuario
                        $record->orders()
                            ->whereNotIn('status', ['paid', 'processing', 'ready'])
                            ->update(['user_id' => null]);

                        // Desvincular sesiones (FK nullOnDelete)
                        $record->generationSessions()->update(['user_id' => null]);

                        // Anonimizar usuario (conservar registro para FK de orders pagadas)
                        $record->update([
                            'email'              => "deleted_{$record->id}@anon.invalid",
                            'first_name'         => null,
                            'last_name'          => null,
                            'ip_address'         => null,
                            'user_agent'         => null,
                            'marketing_opt_out_at' => now(),
                        ]);

                        Notification::make()
                            ->title("Usuario {$email} eliminado y anonimizado")
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
                Infolists\Components\Section::make('Datos del usuario')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('first_name')->label('Nombre')->placeholder('—'),
                        Infolists\Components\TextEntry::make('last_name')->label('Apellido')->placeholder('—'),
                        Infolists\Components\TextEntry::make('locale')->badge(),
                        Infolists\Components\TextEntry::make('country_code')->label('País')->placeholder('—'),
                        Infolists\Components\IconEntry::make('admin_env')
                            ->label('Admin (.env)')
                            ->boolean()
                            ->getStateUsing(fn (User $record): bool =>
                                in_array($record->email, config('genpic.admin_emails', []), true)
                            ),
                        Infolists\Components\IconEntry::make('marketing_opt_out_at')
                            ->label('Marketing')
                            ->boolean()
                            ->getStateUsing(fn (User $record): bool => !$record->hasOptedOutOfMarketing())
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->tooltip(fn (User $record) => $record->hasOptedOutOfMarketing()
                                ? 'Desuscrito el ' . $record->marketing_opt_out_at->format('d/m/Y H:i')
                                : 'Suscrito a marketing'),
                        Infolists\Components\TextEntry::make('ip_address')->label('IP'),
                        Infolists\Components\TextEntry::make('last_seen_at')->label('Última visita')->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('created_at')->label('Registro')->dateTime('d/m/Y H:i'),
                    ]),

                Infolists\Components\Section::make('Importación')
                    ->columns(3)
                    ->visible(fn (User $record) => $record->import_source !== null)
                    ->schema([
                        Infolists\Components\TextEntry::make('import_source')->label('Origen')->badge()->color('info'),
                        Infolists\Components\TextEntry::make('import_batch')->label('Lote')->placeholder('—'),
                        Infolists\Components\TextEntry::make('timezone')->label('Zona horaria')->placeholder('—'),
                    ]),

                Infolists\Components\Section::make('Pedidos')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('orders')
                            ->schema([
                                Infolists\Components\TextEntry::make('uuid')->label('ID')->limit(8),
                                Infolists\Components\TextEntry::make('total_cents')->label('Total')
                                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.') . '€'),
                                Infolists\Components\TextEntry::make('status')->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'paid' => 'warning', 'ready' => 'success', 'failed' => 'danger', default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('paid_at')->label('Pagado')->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(4),
                    ]),

                Infolists\Components\Section::make('Sesiones de generación')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('generationSessions')
                            ->schema([
                                Infolists\Components\TextEntry::make('uuid')->label('ID')->limit(8),
                                Infolists\Components\TextEntry::make('style_key')->label('Estilo'),
                                Infolists\Components\TextEntry::make('status')->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'watermarked' => 'success', 'processing' => 'info', 'failed' => 'danger', default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view'  => Pages\ViewUser::route('/{record}'),
        ];
    }
}
