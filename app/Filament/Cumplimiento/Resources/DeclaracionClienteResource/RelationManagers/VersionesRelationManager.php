<?php

namespace App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\RelationManagers;

use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Models\DeclaracionCliente;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VersionesRelationManager extends RelationManager
{
    protected static string $relationship = 'versiones';

    protected static ?string $title = 'Historial de versiones';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('NumeroVersion', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('NumeroVersion')->label('Versión')->badge(),
                Tables\Columns\TextColumn::make('FechaDeclaracion')->label('Fecha')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('Ocupacion')->label('Ocupación')->searchable(),
                Tables\Columns\TextColumn::make('Estado')->badge()
                    ->formatStateUsing(fn (string $state): string => EstadoDeclaracion::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => $state === 'FINALIZADO' ? 'success' : 'warning'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (DeclaracionCliente $record): string => DeclaracionClienteResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->visible(fn (DeclaracionCliente $record): bool => $record->Estado === 'BORRADOR')
                    ->url(fn (DeclaracionCliente $record): string => DeclaracionClienteResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
