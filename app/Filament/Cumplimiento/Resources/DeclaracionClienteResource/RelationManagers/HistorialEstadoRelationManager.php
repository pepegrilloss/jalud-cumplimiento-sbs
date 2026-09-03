<?php

namespace App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HistorialEstadoRelationManager extends RelationManager
{
    protected static string $relationship = 'historialEstado';

    protected static ?string $title = 'Historial de bajas y reactivaciones';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                Tables\Columns\TextColumn::make('accion')->label('Acción')->badge()
                    ->color(fn (string $state): string => $state === 'DAR_DE_BAJA' ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'DAR_DE_BAJA' ? 'Baja' : 'Reactivación'),
                Tables\Columns\TextColumn::make('old_values.situacion')->label('Estado anterior'),
                Tables\Columns\TextColumn::make('new_values.situacion')->label('Estado nuevo'),
                Tables\Columns\TextColumn::make('new_values.motivo')->label('Motivo')->wrap(),
                Tables\Columns\TextColumn::make('user.name')->label('Usuario')->placeholder('Sistema'),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
