<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistroClienteResource\Pages;
use App\Filament\Resources\RegistroClienteResource\RelationManagers\PersonasRelationManager;
use App\Helpers\UsuarioHelper;
use App\Models\Cliente;
use App\Models\EnlaceCliente;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RegistroClienteResource extends Resource
{
    protected static ?string $model = EnlaceCliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Control de garita';

    protected static ?string $modelLabel = 'Visita cerrada';

    protected static ?string $pluralModelLabel = 'Visitas cerradas';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return UsuarioHelper::esTI() || UsuarioHelper::esComercial() || UsuarioHelper::esGarita();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('cliente')
            ->whereNotNull('CERRADO_AT');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('CERRADO_AT', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ID_MP_VST_ENLACE_CLIENTE')
                    ->label('Visita')
                    ->formatStateUsing(fn ($state): string => 'VIS-'.str_pad((string) $state, 6, '0', STR_PAD_LEFT))
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('cliente.RAZON_SOCIAL')
                    ->label('Cliente o razón social')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('registros_count')
                    ->label('Personas registradas')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('CERRADO_AT')
                    ->label('Cerrada el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('registros_ingresados_count')
                    ->label('Dentro de planta')
                    ->counts([
                        'registros as registros_ingresados_count' => fn (Builder $query): Builder => $query
                            ->whereNotNull('INGRESO_AT')
                            ->whereNull('SALIDA_AT'),
                    ])
                    ->badge()
                    ->color('warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cliente')
                    ->label('Cliente')
                    ->options(fn (): array => Cliente::query()
                        ->orderBy('RAZON_SOCIAL')
                        ->pluck('RAZON_SOCIAL', 'ID_MP_VST_CLIENTE')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $clienteId): Builder => $query->where('ID_MP_VST_CLIENTE', $clienteId),
                    ))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver personas'),
            ])
            ->emptyStateHeading('No hay visitas cerradas pendientes de control')
            ->emptyStateDescription('Las visitas aparecerán aquí recién cuando el cliente cierre su registro de personas.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Resumen de la visita')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('ID_MP_VST_ENLACE_CLIENTE')
                        ->label('Código de visita')
                        ->formatStateUsing(fn ($state): string => 'VIS-'.str_pad((string) $state, 6, '0', STR_PAD_LEFT)),
                    Infolists\Components\TextEntry::make('cliente.RAZON_SOCIAL')
                        ->label('Cliente o razón social')
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('CERRADO_AT')
                        ->label('Registro cerrado el')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            PersonasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrosClientes::route('/'),
            'view' => Pages\ViewRegistroCliente::route('/{record}'),
        ];
    }
}
