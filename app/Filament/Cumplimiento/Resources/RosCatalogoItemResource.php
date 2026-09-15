<?php

namespace App\Filament\Cumplimiento\Resources;

use App\Filament\Cumplimiento\Resources\RosCatalogoItemResource\Pages;
use App\Models\RosCatalogoItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RosCatalogoItemResource extends Resource
{
    protected static ?string $model = RosCatalogoItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Catálogos y Mantenimiento';
    protected static ?string $navigationLabel = 'Catálogos ROS';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'ros-catalogos';
    protected static ?string $modelLabel = 'ítem de catálogo ROS';
    protected static ?string $pluralModelLabel = 'Catálogos ROS';

    public static function canViewAny(): bool { return auth()->user()?->can('gestionar_catalogos_sbs') ?? false; }
    public static function canCreate(): bool { return static::canViewAny(); }
    public static function canEdit($record): bool { return static::canViewAny(); }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('Tipo')->required()->options([
                'DELITO_PRECEDENTE' => 'Delito precedente', 'TIPO_SUJETO' => 'Tipo de sujeto obligado',
                'ESTADO_CIVIL' => 'Estado civil', 'CONDICION_PARTICIPACION' => 'Condición de participación',
                'VIA' => 'Vía', 'TIPO_DOCUMENTO' => 'Documento de identidad', 'MONEDA' => 'Moneda',
                'TIPOLOGIA' => 'Tipología', 'SECTOR' => 'Sector económico', 'ACTIVIDAD' => 'Actividad económica',
                'PRODUCTO_SERVICIO' => 'Producto o servicio', 'SENAL_REPORTADO' => 'Señal del reportado',
                'SENAL_OPERACION' => 'Señal de la operación',
            ])->searchable(),
            Forms\Components\TextInput::make('Codigo')->required()->maxLength(30),
            Forms\Components\Textarea::make('Nombre')->label('Descripción')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('Grupo')->maxLength(120)->helperText('Obligatorio para señales y actividades agrupadas.'),
            Forms\Components\TextInput::make('Orden')->numeric()->integer()->default(0)->required(),
            Forms\Components\Toggle::make('Activo')->default(true),
            Forms\Components\TextInput::make('VersionNormativa')->default('SBS-6561-2009')->required()->maxLength(40),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('Tipo')->columns([
            Tables\Columns\TextColumn::make('Tipo')->badge()->searchable(),
            Tables\Columns\TextColumn::make('Codigo')->searchable(),
            Tables\Columns\TextColumn::make('Nombre')->label('Descripción')->wrap()->searchable(),
            Tables\Columns\TextColumn::make('Grupo')->toggleable(),
            Tables\Columns\IconColumn::make('Activo')->boolean(),
            Tables\Columns\TextColumn::make('Orden')->sortable(),
        ])->filters([Tables\Filters\SelectFilter::make('Tipo')->options(fn (): array => RosCatalogoItem::query()->distinct()->orderBy('Tipo')->pluck('Tipo', 'Tipo')->all()), Tables\Filters\TernaryFilter::make('Activo')])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array { return ['index' => Pages\ListRosCatalogoItems::route('/'), 'create' => Pages\CreateRosCatalogoItem::route('/create'), 'edit' => Pages\EditRosCatalogoItem::route('/{record}/edit')]; }
}
