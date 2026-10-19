<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtisticStyleResource\Pages;
use App\Models\ArtisticStyle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArtisticStyleResource extends Resource
{
    protected static ?string $model = ArtisticStyle::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $modelLabel = 'Estilo artístico';

    protected static ?string $pluralModelLabel = 'Estilos artísticos';

    protected static ?string $navigationGroup = 'Catálogo';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificación')
                    ->schema([
                        Forms\Components\TextInput::make('style_key')
                            ->label('Clave de estilo')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('Traducciones')
                    ->schema([
                        Forms\Components\Tabs::make('translations')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('ES')
                                    ->schema([
                                        Forms\Components\TextInput::make('label.es')->label('Label ES')->required(),
                                        Forms\Components\Textarea::make('description.es')->label('Descripción ES'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('EN')
                                    ->schema([
                                        Forms\Components\TextInput::make('label.en')->label('Label EN'),
                                        Forms\Components\Textarea::make('description.en')->label('Descripción EN'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('FR')
                                    ->schema([
                                        Forms\Components\TextInput::make('label.fr')->label('Label FR'),
                                        Forms\Components\Textarea::make('description.fr')->label('Descripción FR'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('PT')
                                    ->schema([
                                        Forms\Components\TextInput::make('label.pt')->label('Label PT'),
                                        Forms\Components\Textarea::make('description.pt')->label('Descripción PT'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('IT')
                                    ->schema([
                                        Forms\Components\TextInput::make('label.it')->label('Label IT'),
                                        Forms\Components\Textarea::make('description.it')->label('Descripción IT'),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Prompt IA')
                    ->description('Prompts utilizados para la generación. Solo lectura.')
                    ->schema([
                        Forms\Components\Textarea::make('base_prompt')
                            ->label('Base Prompt (EN)')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('negative_prompt')
                            ->label('Negative Prompt')
                            ->rows(2)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('style_strength')
                            ->label('Fuerza del estilo')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('Configuración')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('active')
                            ->label('Activo')
                            ->helperText('Si se muestra en la web'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
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

                Tables\Columns\TextColumn::make('style_key')
                    ->label('Clave')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label.es')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->getStateUsing(function ($record): string {
                        $prefix = explode('_', $record->style_key)[0] ?? $record->style_key;
                        return match ($prefix) {
                            'classic' => 'Clásico',
                            'modern' => 'Moderno',
                            'pop' => 'Pop Art',
                            'watercolor' => 'Acuarela',
                            'oil' => 'Óleo',
                            'cartoon' => 'Cartoon',
                            'renaissance' => 'Renacimiento',
                            'impressionist' => 'Impresionista',
                            default => ucfirst($prefix),
                        };
                    })
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('base_prompt')
                    ->label('Prompt')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->base_prompt)
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('style_strength')
                    ->label('Fuerza')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtisticStyles::route('/'),
            'edit' => Pages\EditArtisticStyle::route('/{record}/edit'),
        ];
    }
}
