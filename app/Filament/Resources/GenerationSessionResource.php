<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GenerationSessionResource\Pages;
use App\Models\GenerationSession;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GenerationSessionResource extends Resource
{
    protected static ?string $model = GenerationSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $modelLabel = 'Sesión de generación';

    protected static ?string $pluralModelLabel = 'Sesiones de generación';

    protected static ?string $navigationGroup = 'Generaciones';

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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── Imágenes ────────────────────────────────────────
                // Las imágenes se sirven por streaming (/{admin_path}/media/...) —
                // protegidas por sesión de admin + IP. Sin URLs firmadas (regla negocio nº 4).
                // Si el archivo fue purgado, ImageEntry muestra el placeholder.
                Infolists\Components\Section::make('Imágenes')
                    ->icon('heroicon-o-photo')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\ImageEntry::make('original_photo_path')
                            ->label('Foto original')
                            ->state(fn ($record) => $record->original_photo_path
                                ? route('admin.media.original', ['session' => $record->uuid])
                                : null)
                            ->url(fn ($record) => $record->original_photo_path
                                ? route('admin.media.original', ['session' => $record->uuid])
                                : null, shouldOpenInNewTab: true)
                            ->placeholder('No disponible / purgada')
                            ->height(200)
                            ->checkFileExistence(false),

                        Infolists\Components\ImageEntry::make('latestGeneration.raw_image_path')
                            ->label('Generada (raw)')
                            ->state(fn ($record) => $record->latestGeneration?->raw_image_path
                                ? route('admin.media.generation', [
                                    'generation' => $record->latestGeneration->id,
                                    'kind' => 'raw',
                                ])
                                : null)
                            ->url(fn ($record) => $record->latestGeneration?->raw_image_path
                                ? route('admin.media.generation', [
                                    'generation' => $record->latestGeneration->id,
                                    'kind' => 'raw',
                                ])
                                : null, shouldOpenInNewTab: true)
                            ->placeholder('No disponible / purgada')
                            ->height(200)
                            ->checkFileExistence(false),

                        Infolists\Components\ImageEntry::make('latestGeneration.watermarked_image_path')
                            ->label('Generada (watermark)')
                            ->state(fn ($record) => $record->latestGeneration?->watermarked_image_path
                                ? route('admin.media.generation', [
                                    'generation' => $record->latestGeneration->id,
                                    'kind' => 'watermarked',
                                ])
                                : null)
                            ->url(fn ($record) => $record->latestGeneration?->watermarked_image_path
                                ? route('admin.media.generation', [
                                    'generation' => $record->latestGeneration->id,
                                    'kind' => 'watermarked',
                                ])
                                : null, shouldOpenInNewTab: true)
                            ->placeholder('No disponible / purgada')
                            ->height(200)
                            ->checkFileExistence(false),
                    ]),

                // ── Sesión ──────────────────────────────────────────
                Infolists\Components\Section::make('Sesión')
                    ->icon('heroicon-o-sparkles')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('uuid')
                            ->label('UUID')
                            ->copyable()
                            ->copyMessage('UUID copiado')
                            ->columnSpan(2),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'watermarked', 'completed' => 'success',
                                'processing', 'uploading', 'validating' => 'info',
                                'generating' => 'warning',
                                'failed' => 'danger',
                                'paid' => 'success',
                                'pending', 'expired' => 'gray',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('style_key')
                            ->label('Estilo')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('locale')
                            ->label('Idioma')
                            ->badge(),

                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP')
                            ->placeholder('—'),

                        Infolists\Components\IconEntry::make('face_detected')
                            ->label('Rostro detectado')
                            ->boolean(),

                        Infolists\Components\TextEntry::make('ab_variant')
                            ->label('Variante A/B (orientación)')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i:s'),

                        Infolists\Components\TextEntry::make('email_captured_at')
                            ->label('Email capturado')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('Sin email'),

                        Infolists\Components\TextEntry::make('expires_at')
                            ->label('Expira')
                            ->dateTime('d/m/Y H:i:s')
                            ->color(fn ($record) => $record->expires_at?->isPast() ? 'danger' : null)
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('purged_at')
                            ->label('Purgado')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('No purgado'),
                    ]),

                // ── Usuario ─────────────────────────────────────────
                Infolists\Components\Section::make('Usuario')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->placeholder('Sin usuario'),

                        Infolists\Components\TextEntry::make('user.last_seen_at')
                            ->label('Última visita')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—'),
                    ]),

                // ── Generación (última) ─────────────────────────────
                Infolists\Components\Section::make('Generación (última)')
                    ->icon('heroicon-o-paint-brush')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('latestGeneration.status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'completed' => 'success',
                                'processing' => 'info',
                                'queued' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('Sin generación'),

                        Infolists\Components\TextEntry::make('latestGeneration.provider')
                            ->label('Proveedor')
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'fal_ai' => 'success',
                                'openrouter' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state) => match ($state) {
                                'fal_ai' => 'fal.ai',
                                'openrouter' => 'OpenRouter',
                                default => $state ?? '—',
                            })
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('latestGeneration.provider_model')
                            ->label('Modelo')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('latestGeneration.cost_usd')
                            ->label('Coste')
                            ->formatStateUsing(fn ($state) => $state !== null ? '$'.number_format((float) $state, 4) : '—')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('latestGeneration.elapsed_ms')
                            ->label('Duración')
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format((int) $state / 1000, 1).'s' : '—')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('latestGeneration.attempt_number')
                            ->label('Intento')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('latestGeneration.error_message')
                            ->label('Error')
                            ->columnSpanFull()
                            ->color('danger')
                            ->placeholder('Sin errores')
                            ->visible(fn ($record) => $record->latestGeneration?->status === 'failed'),

                        Infolists\Components\TextEntry::make('latestGeneration.created_at')
                            ->label('Creada')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—'),
                    ]),

                // ── Archivos ────────────────────────────────────────
                Infolists\Components\Section::make('Archivos')
                    ->icon('heroicon-o-document')
                    ->columns(1)
                    ->schema([
                        Infolists\Components\TextEntry::make('original_photo_path')
                            ->label('Foto original')
                            ->placeholder('Sin archivo')
                            ->icon('heroicon-m-photo')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('latestGeneration.raw_image_path')
                            ->label('Imagen generada (raw)')
                            ->placeholder('Sin archivo')
                            ->icon('heroicon-m-photo')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('latestGeneration.watermarked_image_path')
                            ->label('Imagen con watermark')
                            ->placeholder('Sin archivo')
                            ->icon('heroicon-m-photo')
                            ->columnSpanFull(),
                    ]),

                // ── Pedidos ─────────────────────────────────────────
                Infolists\Components\Section::make('Pedidos')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('orders')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('uuid')
                                    ->label('UUID pedido')
                                    ->copyable()
                                    ->copyMessage('UUID copiado')
                                    ->limit(12)
                                    ->tooltip(fn ($record) => $record->uuid),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'paid', 'ready' => 'success',
                                        'processing' => 'info',
                                        'pending' => 'warning',
                                        'refunded' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('total_cents')
                                    ->label('Total')
                                    ->formatStateUsing(fn ($state) => $state !== null
                                        ? number_format((int) $state / 100, 2).' €'
                                        : '—'),

                                Infolists\Components\TextEntry::make('paid_at')
                                    ->label('Pagado')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->placeholder('No pagado'),
                            ])
                            ->placeholder('Sin pedidos'),
                    ])
                    ->collapsible(),
            ]);
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

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email usuario')
                    ->searchable()
                    ->placeholder('Sin usuario')
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('style_key')
                    ->label('Estilo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('latestGeneration.provider_model')
                    ->label('Modelo IA')
                    ->placeholder('—')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->latestGeneration?->provider_model)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('latestGeneration.provider')
                    ->label('Proveedor')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'fal_ai' => 'success',
                        'openrouter' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'fal_ai' => 'fal.ai',
                        'openrouter' => 'OpenRouter',
                        default => $state ?? '—',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'uploading' => 'info',
                        'validating' => 'info',
                        'generating' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'expired' => 'gray',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('face_detected')
                    ->label('Rostro')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('locale')
                    ->label('Idioma')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ab_variant')
                    ->label('Variante A/B')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at?->isPast() ? 'danger' : null),

                Tables\Columns\TextColumn::make('purged_at')
                    ->label('Purgado')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('No purgado')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        'uploading' => 'Subiendo',
                        'validating' => 'Validando',
                        'generating' => 'Generando',
                        'completed' => 'Completada',
                        'failed' => 'Fallida',
                        'expired' => 'Expirada',
                        'paid' => 'Pagada',
                    ]),

                Tables\Filters\SelectFilter::make('locale')
                    ->label('Idioma')
                    ->options([
                        'es' => 'Español',
                        'en' => 'Inglés',
                    ]),

                Tables\Filters\TernaryFilter::make('face_detected')
                    ->label('Rostro detectado'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGenerationSessions::route('/'),
            'view' => Pages\ViewGenerationSession::route('/{record}'),
        ];
    }
}
