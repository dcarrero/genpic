<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'Pedido';

    protected static ?string $pluralModelLabel = 'Pedidos';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('UUID copiado')
                    ->limit(12)
                    ->tooltip(fn ($record) => $record->uuid),

                Tables\Columns\TextColumn::make('session.user.email')
                    ->label('Email usuario')
                    ->searchable()
                    ->placeholder('Sin usuario')
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.') . '€')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'paid' => 'warning',
                        'processing' => 'info',
                        'ready' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('download_ready')
                    ->label('Descarga lista')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stripe_checkout_id')
                    ->label('Stripe Checkout')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->stripe_checkout_id)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->date('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'processing' => 'Procesando',
                        'ready' => 'Listo',
                        'failed' => 'Fallido',
                    ]),

                Tables\Filters\TernaryFilter::make('download_ready')
                    ->label('Descarga lista'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('sync_stripe')
                    ->label('Sincronizar Stripe')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Sincronizar con Stripe')
                    ->modalDescription('Consultará el estado del pago en Stripe y actualizará la order si está pagada.')
                    ->visible(fn (Order $record): bool => $record->status === 'pending' && $record->stripe_checkout_id)
                    ->action(function (Order $record) {
                        try {
                            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                            $cs = $stripe->checkout->sessions->retrieve($record->stripe_checkout_id);

                            if ($cs->payment_status === 'paid' && $cs->status === 'complete') {
                                $confirmed = app(\App\Services\OrderFulfillmentService::class)->confirmPayment(
                                    $record,
                                    $cs->payment_intent,
                                    $cs->customer,
                                    'admin:sync'
                                );

                                Notification::make()
                                    ->title($confirmed ? 'Pago confirmado' : 'Ya estaba procesada')
                                    ->body($confirmed ? "Order {$record->uuid} marcada como pagada." : "Order ya tenía estado {$record->status}")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No pagado')
                                    ->body("Stripe dice: payment={$cs->payment_status}, status={$cs->status}")
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error Stripe')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reprocess_formats')
                    ->label('Reprocesar formatos')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Reprocesar formatos')
                    ->modalDescription('Regenerará los archivos de descarga para esta order.')
                    ->visible(fn (Order $record): bool => in_array($record->status, ['paid', 'processing']) && !$record->download_ready)
                    ->action(function (Order $record) {
                        GenerateFormatsJob::dispatch($record);
                        Log::info('Formatos redespachados desde admin', ['order' => $record->uuid]);

                        Notification::make()
                            ->title('Job despachado')
                            ->body('GenerateFormatsJob en cola. Los archivos estarán listos en segundos.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
