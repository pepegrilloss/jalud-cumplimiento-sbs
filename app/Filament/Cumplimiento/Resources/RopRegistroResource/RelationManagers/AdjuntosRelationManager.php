<?php

namespace App\Filament\Cumplimiento\Resources\RopRegistroResource\RelationManagers;

use App\Models\RopRegistro;
use App\Models\RopPeriodo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AdjuntosRelationManager extends RelationManager
{
    protected static string $relationship = 'adjuntos';
    protected static ?string $title = 'Evidencias adjuntas';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('RutaArchivo')
                ->label('Archivo de evidencia')
                ->disk('local')
                ->directory('cumplimiento-sbs/rop-evidencias')
                ->storeFileNamesIn('NombreOriginal')
                ->acceptedFileTypes([
                    'application/pdf', 'image/jpeg', 'image/png', 'text/plain',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                ->maxSize(20480)
                ->required(),
            Forms\Components\Select::make('Categoria')->label('Tipo de evidencia')->options([
                'DOCUMENTO' => 'Documento', 'IMAGEN' => 'Imagen', 'OTRO' => 'Otro',
            ]),
            Forms\Components\TextInput::make('Descripcion')->label('Descripción')->maxLength(255),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('NombreOriginal')->label('Archivo')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('Categoria')->label('Categoría')->placeholder('—'),
            Tables\Columns\TextColumn::make('Descripcion')->label('Descripción')->placeholder('—')->wrap(),
            Tables\Columns\TextColumn::make('TamanioBytes')->label('Tamaño')->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 1).' KB' : '—'),
            Tables\Columns\TextColumn::make('subidoPor.name')->label('Subido por')->placeholder('Sistema'),
            Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
        ])->headerActions([
            Tables\Actions\CreateAction::make()
                ->label('Adjuntar evidencia')
                ->visible(fn (): bool => auth()->user()?->can('completar_rop')
                    && $this->getOwnerRecord()->periodo?->Estado !== RopPeriodo::ESTADO_CERRADO),
        ])->actions([
            Tables\Actions\Action::make('ver')
                ->label('Ver')
                ->icon('heroicon-o-eye')
                ->url(fn ($record): string => route('rop.adjuntos.ver', $record))
                ->openUrlInNewTab(),
            Tables\Actions\Action::make('descargar')
                ->label('Descargar')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn ($record): string => route('rop.adjuntos.descargar', $record)),
            Tables\Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->can('completar_rop')
                    && $this->getOwnerRecord()->periodo?->Estado !== RopPeriodo::ESTADO_CERRADO),
        ])->bulkActions([]);
    }
}
