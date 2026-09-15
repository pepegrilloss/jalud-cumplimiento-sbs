<?php

namespace App\Filament\Cumplimiento\Resources;

use App\Filament\Cumplimiento\Resources\RosConfiguracionResource\Pages;
use App\Models\RopCatalogoItem;
use App\Models\RosConfiguracion;
use App\Models\Sede;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RosConfiguracionResource extends Resource
{
    protected static ?string $model = RosConfiguracion::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Cumplimiento';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Configuración SBS por sede';

    protected static ?string $slug = 'ros-configuracion-sedes';

    protected static ?string $modelLabel = 'configuración SBS';

    protected static ?string $pluralModelLabel = 'Configuración SBS por sede';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('gestionar_catalogos_sbs') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('SedeID')->label('Sede de Jalud')
                ->options(fn (): array => Sede::query()->operativas()->whereKey(auth()->user()?->getEffectiveSedeId())->pluck('Nombre', 'SedeID')->all())
                ->default(fn () => auth()->user()?->getEffectiveSedeId())
                ->unique(ignoreRecord: true)->required()->disabled()->dehydrated(),
            Forms\Components\TextInput::make('UmbralRop')->label('Umbral de ingreso al ROP')->numeric()->prefix('S/')->minValue(0)->required(),
            Forms\Components\Select::make('TipoSujetoObligado')->label('Tipo de sujeto obligado (Tabla N.° 1 ROP)')
                ->options(fn (): array => RopCatalogoItem::query()->where('Tipo', RopCatalogoItem::TIPO_SUJETO_OBLIGADO)->where('Activo', true)->orderBy('Orden')->pluck('Nombre', 'Nombre')->all())
                ->searchable()->required(),
            Forms\Components\TextInput::make('CodigoEmpresa')->label('Código de empresa SBS')->required()->maxLength(80),
            Forms\Components\TextInput::make('CodigoOficialCumplimiento')->label('Código del oficial de cumplimiento')->required()->maxLength(80),
            Forms\Components\TextInput::make('OficinaInformante')->label('Oficina informante')->required()->maxLength(180),
            Forms\Components\TextInput::make('Departamento')->required()->maxLength(120),
            Forms\Components\TextInput::make('Provincia')->required()->maxLength(120),
            Forms\Components\TextInput::make('Distrito')->required()->maxLength(120),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('SedeID')->label('Sede')->formatStateUsing(fn ($state): string => Sede::query()->find($state)?->Nombre ?? "Sede #{$state}"),
            Tables\Columns\TextColumn::make('UmbralRop')->label('Umbral ROP')->money('PEN')->sortable(),
            Tables\Columns\TextColumn::make('CodigoEmpresa')->label('Código SBS')->searchable(),
            Tables\Columns\TextColumn::make('CodigoOficialCumplimiento')->label('Oficial')->searchable(),
            Tables\Columns\TextColumn::make('OficinaInformante')->label('Oficina')->searchable(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRosConfiguraciones::route('/'), 'create' => Pages\CreateRosConfiguracion::route('/create'), 'edit' => Pages\EditRosConfiguracion::route('/{record}/edit')];
    }
}
