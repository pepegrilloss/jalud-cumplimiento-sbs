<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers\EnlacesRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\RegistrosRelationManager;
use App\Helpers\UsuarioHelper;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return UsuarioHelper::esTI() || UsuarioHelper::esComercial();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del cliente')
                ->description('La razón social aparecerá automáticamente en cada enlace público.')
                ->icon('heroicon-o-building-office')
                ->schema([
                    Forms\Components\TextInput::make('RAZON_SOCIAL')
                        ->label('Cliente o razón social')
                        ->required()
                        ->maxLength(200)
                        ->unique(ignoreRecord: true)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('ACTIVO')
                        ->label('Cliente activo')
                        ->helperText('Al desactivarlo, ninguno de sus enlaces permitirá nuevos registros.')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('CREATED_AT', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('RAZON_SOCIAL')
                    ->label('Cliente o razón social')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('enlaces_count')
                    ->label('Enlaces')
                    ->counts('enlaces')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('registros_count')
                    ->label('Personas registradas')
                    ->counts('registros')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('ACTIVO')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('CREATED_AT')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ACTIVO')->label('Estado'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Enlaces'),
                Tables\Actions\EditAction::make(),
            ])
            ->emptyStateHeading('Aún no hay clientes')
            ->emptyStateDescription('Crea un cliente para generar su primer enlace de registro.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    public static function getRelations(): array
    {
        return [
            EnlacesRelationManager::class,
            RegistrosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'view' => Pages\ViewCliente::route('/{record}'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
