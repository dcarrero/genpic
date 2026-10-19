<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailSequenceResource\Pages;
use App\Models\EmailSequence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailSequenceResource extends Resource
{
    protected static ?string $model = EmailSequence::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $modelLabel = 'Secuencia Email';

    protected static ?string $pluralModelLabel = 'Secuencias Email';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $locales = config('genpic.locales', ['es', 'en']);
        $localeLabels = [
            'es' => 'Español', 'en' => 'English', 'fr' => 'Français',
            'de' => 'Deutsch', 'it' => 'Italiano', 'pt' => 'Português',
        ];

        // Construir tabs de contenido por idioma
        $contentTabs = [];
        foreach ($locales as $locale) {
            $label = $localeLabels[$locale] ?? strtoupper($locale);
            $contentTabs[] = Forms\Components\Tabs\Tab::make($label)
                ->icon(self::localeIcon($locale))
                ->schema([
                    Forms\Components\TextInput::make("subject.{$locale}")
                        ->label('Asunto')
                        ->required($locale === 'es')
                        ->maxLength(255),

                    Forms\Components\TextInput::make("heading.{$locale}")
                        ->label('Título del email')
                        ->maxLength(255),

                    Forms\Components\RichEditor::make("body.{$locale}")
                        ->label('Cuerpo del email')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline',
                            'link', 'orderedList', 'bulletList',
                            'h2', 'h3',
                        ])
                        ->helperText('HTML compatible con email. Variables: :days (días restantes), :product (producto).'),

                    Forms\Components\TextInput::make("cta_text.{$locale}")
                        ->label('Texto del botón (CTA)')
                        ->maxLength(100),

                    Forms\Components\TextInput::make("coupon_text.{$locale}")
                        ->label('Texto del cupón')
                        ->maxLength(255)
                        ->helperText('Solo si esta secuencia tiene descuento.'),
                ]);
        }

        return $form->schema([
            Forms\Components\Section::make('Configuración')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre interno')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('sequence_type')
                    ->label('Tipo')
                    ->options(['abandonment' => 'Abandono (sin compra)', 'upgrade' => 'Upgrade (compra parcial)'])
                    ->required(),

                Forms\Components\TextInput::make('email_number')
                    ->label('N.º email en secuencia')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(10),

                Forms\Components\TextInput::make('delay_hours')
                    ->label('Horas desde creación/compra')
                    ->numeric()
                    ->required()
                    ->helperText('Horas que deben pasar antes de enviar este email'),

                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true),
            ])->columns(3),

            Forms\Components\Section::make('Descuento automático')->schema([
                Forms\Components\TextInput::make('discount_percent')
                    ->label('Descuento (%)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->helperText('0 = sin descuento'),

                Forms\Components\Select::make('discount_product_key')
                    ->label('Producto con descuento')
                    ->options(fn () => \App\Models\Product::active()->pluck('name_es', 'product_key'))
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Contenido por idioma')
                ->description('Un tab por cada idioma configurado. Añade idiomas en config/genpic.php → locales.')
                ->schema([
                    Forms\Components\Tabs::make('translations')
                        ->tabs($contentTabs)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Notas')->schema([
                Forms\Components\Textarea::make('description')
                    ->label('Descripción interna')
                    ->rows(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sequence_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'abandonment' => 'warning',
                        'upgrade' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'abandonment' => 'Abandono',
                        'upgrade' => 'Upgrade',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('email_number')
                    ->label('#')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                Tables\Columns\TextColumn::make('delay_hours')
                    ->label('Delay')
                    ->formatStateUsing(function (int $state) {
                        if ($state < 24) return $state . 'h';
                        return round($state / 24, 1) . 'd';
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('discount_percent')
                    ->label('Descuento')
                    ->formatStateUsing(fn (int $state) => $state > 0 ? $state . '%' : '—')
                    ->alignCenter()
                    ->color(fn (int $state) => $state > 0 ? 'success' : null),

                Tables\Columns\TextColumn::make('subject_preview')
                    ->label('Asunto (ES)')
                    ->state(fn (EmailSequence $record) => $record->subject('es'))
                    ->limit(40),

                Tables\Columns\TextColumn::make('translations_count')
                    ->label('Idiomas')
                    ->state(function (EmailSequence $record) {
                        $filled = collect($record->subject ?? [])
                            ->filter(fn ($v) => !empty($v))
                            ->keys();
                        return $filled->map(fn ($l) => strtoupper($l))->implode(', ');
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sequence_type')
                    ->label('Tipo')
                    ->options(['abandonment' => 'Abandono', 'upgrade' => 'Upgrade']),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('sequence_type')
            ->defaultSort('email_number');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailSequences::route('/'),
            'create' => Pages\CreateEmailSequence::route('/create'),
            'edit' => Pages\EditEmailSequence::route('/{record}/edit'),
        ];
    }

    private static function localeIcon(string $locale): string
    {
        return match ($locale) {
            'es' => 'heroicon-o-flag',
            'en' => 'heroicon-o-globe-alt',
            default => 'heroicon-o-language',
        };
    }
}
