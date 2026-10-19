<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        // Solo borrar si nunca se ha usado en un order_item
        return $record->orderItems()->doesntExist();
    }

    public static function canDeleteAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificación')
                    ->schema([
                        Forms\Components\TextInput::make('product_key')
                            ->label('Clave de producto')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('Traducciones')
                    ->schema([
                        Forms\Components\Tabs::make('translations')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('ES')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.es')->label('Nombre ES')->required(),
                                        Forms\Components\Textarea::make('description.es')->label('Descripción ES'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('EN')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.en')->label('Nombre EN'),
                                        Forms\Components\Textarea::make('description.en')->label('Descripción EN'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('FR')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.fr')->label('Nombre FR'),
                                        Forms\Components\Textarea::make('description.fr')->label('Descripción FR'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('PT')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.pt')->label('Nombre PT'),
                                        Forms\Components\Textarea::make('description.pt')->label('Descripción PT'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('IT')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.it')->label('Nombre IT'),
                                        Forms\Components\Textarea::make('description.it')->label('Descripción IT'),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Precio y configuración')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('price_cents')
                            ->label('Precio (céntimos)')
                            ->numeric()
                            ->required()
                            ->suffix('céntimos = ' . '€')
                            ->helperText('Precio en céntimos de EUR (ej: 990 = 9,90€)')
                            ->minValue(0),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Toggle::make('highlight')
                            ->label('Destacado')
                            ->helperText('Mostrar como producto destacado'),

                        Forms\Components\Toggle::make('active')
                            ->label('Activo')
                            ->helperText('Si se muestra en la web'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_key')
                    ->label('Clave')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name.es')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.') . '€')
                    ->sortable(),

                Tables\Columns\IconColumn::make('highlight')
                    ->label('Destacado')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activo'),

                Tables\Filters\TernaryFilter::make('highlight')
                    ->label('Destacado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Product $record): bool => $record->orderItems()->doesntExist())
                    ->requiresConfirmation(),
            ])
            ->bulkActions([])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\FormatsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
