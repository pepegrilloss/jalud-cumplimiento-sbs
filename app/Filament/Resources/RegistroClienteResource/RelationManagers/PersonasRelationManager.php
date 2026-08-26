<?php

namespace App\Filament\Resources\RegistroClienteResource\RelationManagers;

use App\Models\RegistroCliente;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PersonasRelationManager extends RelationManager
{
    protected static string $relationship = 'registros';

    protected static ?string $title = 'Personas de esta visita';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('CREATED_AT', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('NOMBRES')
                    ->label('Persona')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('DNI')
                    ->label('DNI')
                    ->searchable(),
                Tables\Columns\TextColumn::make('INGRESO_AT')
                    ->label('Ingreso')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->badge()
                    ->color(fn (RegistroCliente $record): string => $record->INGRESO_AT ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('SALIDA_AT')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->badge()
                    ->color(fn (RegistroCliente $record): string => $record->SALIDA_AT ? 'success' : 'gray'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('ver_dni')
                    ->label('Ver DNI')
                    ->icon('heroicon-o-photo')
                    ->url(fn (RegistroCliente $record): string => route('registros-clientes.dni', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('marcar_ingreso')
                    ->label('Marcar ingreso')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RegistroCliente $record): bool => $record->INGRESO_AT === null)
                    ->action(fn (RegistroCliente $record) => $record->update(['INGRESO_AT' => now()])),
                Tables\Actions\Action::make('marcar_salida')
                    ->label('Marcar salida')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (RegistroCliente $record): bool => $record->INGRESO_AT !== null && $record->SALIDA_AT === null)
                    ->action(fn (RegistroCliente $record) => $record->update(['SALIDA_AT' => now()])),
            ])
            ->bulkActions([]);
    }
}
