<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use App\Models\RegistroCliente;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RegistrosRelationManager extends RelationManager
{
    protected static string $relationship = 'registros';

    protected static ?string $title = 'Personas registradas de este cliente';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('CREATED_AT', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('NOMBRES')
                    ->label('Persona')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('DNI')
                    ->label('DNI'),
                Tables\Columns\TextColumn::make('INGRESO_AT')
                    ->label('Ingreso')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente'),
                Tables\Columns\TextColumn::make('SALIDA_AT')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente'),
                Tables\Columns\TextColumn::make('CREATED_AT')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('ver_dni')
                    ->label('Ver DNI')
                    ->icon('heroicon-o-photo')
                    ->url(fn (RegistroCliente $record): string => route('registros-clientes.dni', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Este cliente todavía no tiene personas registradas')
            ->emptyStateDescription('Las personas aparecerán aquí cuando el cliente use su enlace.')
            ->emptyStateIcon('heroicon-o-identification');
    }
}
