<?php

namespace App\Filament\Cumplimiento\Resources;

use App\Filament\Cumplimiento\Resources\RopCatalogoItemResource\Pages;
use App\Models\RopCatalogoItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RopCatalogoItemResource extends Resource
{
    protected static ?string $model = RopCatalogoItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationGroup = 'Cumplimiento';

    protected static ?string $navigationLabel = 'Tablas de mantenimiento ROP';

    protected static ?string $modelLabel = 'valor de tabla ROP';

    protected static ?string $pluralModelLabel = 'Tablas de mantenimiento ROP';

    protected static ?int $navigationSort = 2;

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
            Forms\Components\Select::make('Tipo')->label('Tabla')->options(RopCatalogoItem::tipos())->required(),
            Forms\Components\TextInput::make('Codigo')->label('Código interno')->required()->maxLength(80),
            Forms\Components\TextInput::make('Grupo')->label('Sector / grupo')->maxLength(180),
            Forms\Components\Textarea::make('Nombre')->label('Descripción oficial')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('Orden')->numeric()->default(0)->required(),
            Forms\Components\Toggle::make('Activo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('Tipo')->label('Tabla')->formatStateUsing(fn ($state) => RopCatalogoItem::tipos()[$state] ?? $state)->sortable(),
            Tables\Columns\TextColumn::make('Grupo')->label('Sector / grupo')->searchable(),
            Tables\Columns\TextColumn::make('Nombre')->label('Descripción oficial')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('Orden')->sortable(),
            Tables\Columns\IconColumn::make('Activo')->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('Tipo')->label('Tabla')->options(RopCatalogoItem::tipos()),
        ])->actions([Tables\Actions\EditAction::make()])->defaultSort('Tipo')->defaultSort('Orden');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRopCatalogoItems::route('/'),
            'create' => Pages\CreateRopCatalogoItem::route('/create'),
            'edit' => Pages\EditRopCatalogoItem::route('/{record}/edit'),
        ];
    }
}
