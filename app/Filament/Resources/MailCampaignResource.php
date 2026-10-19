<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MailCampaignResource\Pages;
use App\Models\MailCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MailCampaignResource extends Resource
{
    protected static ?string $model = MailCampaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Campañas Email';
    protected static ?int $navigationSort = 80;

    public static function form(Form $form): Form
    {
        $locales = config('genpic.locales', ['es', 'en']);

        return $form->schema([
            Forms\Components\Section::make('Campaña')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la campaña')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('coupon_code')
                    ->label('Código cupón (Stripe)')
                    ->maxLength(50)
                    ->helperText('Ej: FACECO50. Debe existir en Stripe Dashboard.'),

                Forms\Components\Select::make('target_source')
                    ->label('Enviar a grupo')
                    ->options(fn () => \App\Models\User::whereNotNull('import_source')
                        ->distinct()
                        ->pluck('import_source', 'import_source')
                        ->prepend('Todos los usuarios', '')
                        ->toArray()
                    )
                    ->default('')
                    ->helperText('Filtrar por origen de importación'),

                Forms\Components\TextInput::make('batch_size')
                    ->label('Tamaño de lote')
                    ->numeric()
                    ->default(100)
                    ->minValue(10)
                    ->maxValue(500),
            ])->columns(2),

            Forms\Components\Tabs::make('Contenido')->tabs(
                collect($locales)->map(fn ($loc) =>
                    Forms\Components\Tabs\Tab::make(strtoupper($loc))->schema([
                        Forms\Components\TextInput::make("subject.{$loc}")
                            ->label("Asunto ({$loc})")
                            ->required($loc === 'es'),
                        Forms\Components\RichEditor::make("body.{$loc}")
                            ->label("Cuerpo ({$loc})")
                            ->required($loc === 'es')
                            ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'h2', 'h3']),
                    ])
                )->all()
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Campaña')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'sending',
                        'info' => 'paused',
                        'success' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('target_source')
                    ->label('Grupo')
                    ->default('Todos'),
                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Enviados')
                    ->formatStateUsing(fn ($record) => "{$record->sent_count}/{$record->total_recipients}"),
                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Fallos'),
                Tables\Columns\TextColumn::make('coupon_code')
                    ->label('Cupón'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMailCampaigns::route('/'),
            'create' => Pages\CreateMailCampaign::route('/create'),
            'edit'   => Pages\EditMailCampaign::route('/{record}/edit'),
            'view'   => Pages\ViewMailCampaign::route('/{record}'),
        ];
    }
}
