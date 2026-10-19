<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $modelLabel = 'Testimonio';

    protected static ?string $pluralModelLabel = 'Testimonios';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $locales = config('genpic.locales', ['es', 'en']);
        $localeLabels = [
            'es' => 'Español', 'en' => 'English', 'fr' => 'Français',
            'de' => 'Deutsch', 'it' => 'Italiano', 'pt' => 'Português',
        ];

        $tabs = [];
        foreach ($locales as $locale) {
            $label = $localeLabels[$locale] ?? strtoupper($locale);
            $tabs[] = Forms\Components\Tabs\Tab::make($label)->schema([
                Forms\Components\Textarea::make("quote.{$locale}")
                    ->label('Opinión')
                    ->rows(3)
                    ->required($locale === 'es'),

                Forms\Components\TextInput::make("author.{$locale}")
                    ->label('Autor')
                    ->maxLength(100)
                    ->placeholder('Nombre · Ciudad')
                    ->required($locale === 'es'),

                Forms\Components\TextInput::make("tag.{$locale}")
                    ->label('Etiqueta')
                    ->maxLength(50)
                    ->placeholder('Foto de familia, Regalo pareja...'),
            ]);
        }

        return $form->schema([
            Forms\Components\Section::make('Contenido por idioma')
                ->schema([
                    Forms\Components\Tabs::make('translations')->tabs($tabs)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Configuración')->schema([
                Forms\Components\Select::make('rating')
                    ->label('Estrellas')
                    ->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★'])
                    ->default(5),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_preview')
                    ->label('Opinión')
                    ->state(fn (Testimonial $r) => $r->t('quote', 'es'))
                    ->limit(60)
                    ->searchable(query: fn ($query, $search) => $query->where('quote', 'like', "%{$search}%")),

                Tables\Columns\TextColumn::make('author_preview')
                    ->label('Autor')
                    ->state(fn (Testimonial $r) => $r->t('author', 'es')),

                Tables\Columns\TextColumn::make('tag_preview')
                    ->label('Etiqueta')
                    ->state(fn (Testimonial $r) => $r->t('tag', 'es'))
                    ->badge(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('★')
                    ->alignCenter(),

                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
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
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
