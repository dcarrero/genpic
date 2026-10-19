<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryExampleResource\Pages;
use App\Models\GalleryExample;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GalleryExampleResource extends Resource
{
    protected static ?string $model = GalleryExample::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $modelLabel = 'Ejemplo galería';

    protected static ?string $pluralModelLabel = 'Galería de ejemplos';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ejemplo')->schema([
                Forms\Components\Tabs::make('translations')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('ES')
                            ->schema([
                                Forms\Components\TextInput::make('title.es')->label('Título ES')->maxLength(100),
                            ]),
                        Forms\Components\Tabs\Tab::make('EN')
                            ->schema([
                                Forms\Components\TextInput::make('title.en')->label('Título EN')->maxLength(100),
                            ]),
                        Forms\Components\Tabs\Tab::make('FR')
                            ->schema([
                                Forms\Components\TextInput::make('title.fr')->label('Título FR')->maxLength(100),
                            ]),
                        Forms\Components\Tabs\Tab::make('PT')
                            ->schema([
                                Forms\Components\TextInput::make('title.pt')->label('Título PT')->maxLength(100),
                            ]),
                        Forms\Components\Tabs\Tab::make('IT')
                            ->schema([
                                Forms\Components\TextInput::make('title.it')->label('Título IT')->maxLength(100),
                            ]),
                    ]),

                Forms\Components\Select::make('photo_type')
                    ->label('Tipo de foto')
                    ->options([
                        'family'     => 'Familia',
                        'couple'     => 'Pareja',
                        'individual' => 'Retrato individual',
                        'group'      => 'Grupo / Mascota',
                    ])
                    ->required(),

                Forms\Components\Select::make('artistic_style_id')
                    ->label('Estilo artístico')
                    ->relationship('artisticStyle', 'label_es')
                    ->searchable()
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('Configuración')->schema([
                Forms\Components\Toggle::make('featured')
                    ->label('Destacado en landing')
                    ->helperText('Se muestra en la sección de galería de la landing'),

                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title_display')
                    ->label('Título')
                    ->getStateUsing(fn ($record) => $record->t('title'))
                    ->placeholder('Sin título'),

                Tables\Columns\TextColumn::make('style_name')
                    ->label('Estilo')
                    ->getStateUsing(fn ($record) => $record->artisticStyle?->t('label')),

                Tables\Columns\TextColumn::make('photo_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'family' => 'Familia', 'couple' => 'Pareja',
                        'individual' => 'Retrato', 'group' => 'Grupo',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'family' => 'info', 'couple' => 'warning',
                        'individual' => 'success', 'group' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('featured')
                    ->label('Landing')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->trueColor('warning'),

                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('photo_type')
                    ->label('Tipo')
                    ->options([
                        'family' => 'Familia', 'couple' => 'Pareja',
                        'individual' => 'Retrato', 'group' => 'Grupo',
                    ]),

                Tables\Filters\TernaryFilter::make('featured')
                    ->label('Destacado'),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleryExamples::route('/'),
            'edit' => Pages\EditGalleryExample::route('/{record}/edit'),
        ];
    }
}
