<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Líneas del pedido';

    protected static ?string $modelLabel = 'Línea';

    protected static ?string $pluralModelLabel = 'Líneas';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name_es')
                    ->label('Producto'),

                Tables\Columns\TextColumn::make('product.product_key')
                    ->label('Clave producto')
                    ->badge(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_price_cents')
                    ->label('Precio unitario')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.') . '€'),

                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.') . '€'),

                Tables\Columns\IconColumn::make('exports_ready')
                    ->label('Exportaciones listas')
                    ->boolean(),

                Tables\Columns\TextColumn::make('export_paths')
                    ->label('Rutas exportación')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return implode(', ', array_map(fn ($p) => basename($p), $state));
                        }
                        return $state ?? '-';
                    })
                    ->wrap()
                    ->limit(60),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
