<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FormatsRelationManager extends RelationManager
{
    protected static string $relationship = 'formats';

    protected static ?string $title = 'Formatos';

    protected static ?string $modelLabel = 'Formato';

    protected static ?string $pluralModelLabel = 'Formatos';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('format_key')
                    ->label('Clave')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label_es')
                    ->label('Etiqueta (ES)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('label_en')
                    ->label('Etiqueta (EN)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('width_px')
                    ->label('Ancho (px)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('height_px')
                    ->label('Alto (px)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_type')
                    ->label('Tipo archivo')
                    ->badge(),

                Tables\Columns\TextColumn::make('quality')
                    ->label('Calidad')
                    ->suffix('%'),

                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
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
