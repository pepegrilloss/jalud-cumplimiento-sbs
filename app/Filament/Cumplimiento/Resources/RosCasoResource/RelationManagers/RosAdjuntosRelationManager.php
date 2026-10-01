<?php

namespace App\Filament\Cumplimiento\Resources\RosCasoResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RosAdjuntosRelationManager extends RelationManager
{
    protected static string $relationship = 'adjuntos';

    protected static ?string $title = 'Evidencias adjuntas';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('NombreOriginal')->label('Archivo')->wrap(),
                Tables\Columns\TextColumn::make('Descripcion')->label('Descripción')->placeholder('—')->wrap(),
                Tables\Columns\TextColumn::make('TamanioBytes')->label('Tamaño')->formatStateUsing(fn ($state): string => $state ? number_format($state / 1024, 1).' KB' : '—'),
                Tables\Columns\TextColumn::make('subidoPor.name')->label('Subido por')->placeholder('Sistema'),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record): string => route('ros.adjuntos.ver', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record): string => route('ros.adjuntos.descargar', $record)),
            ])
            ->bulkActions([]);
    }
}
