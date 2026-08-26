<?php

namespace App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\RelationManagers;

use App\Models\TrabajadorArchivo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ArchivosAdjuntosRelationManager extends RelationManager
{
    protected static string $relationship = 'archivosAdjuntos';

    protected static ?string $title = 'Documentos adicionales del trabajador';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('TipoDocumento')->label('Tipo de documento')->options([
                'CONVENIO' => 'Convenio',
                'CONTRATO' => 'Contrato',
                'MEMORANDUM' => 'Memorándum',
                'EVIDENCIA' => 'Evidencia',
                'OTRO' => 'Otro',
            ])->required(),
            Forms\Components\TextInput::make('Titulo')->label('Título')->required()->maxLength(200),
            Forms\Components\Textarea::make('Descripcion')->label('Descripción')->rows(3)->columnSpanFull(),
            Forms\Components\FileUpload::make('RutaArchivo')
                ->label('Archivo')
                ->disk('local')
                ->directory('declaraciones/trabajadores/adjuntos')
                ->acceptedFileTypes([
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->maxSize(15360)
                ->storeFileNamesIn('NombreOriginal')
                ->required()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('Titulo')->label('Documento')->searchable(),
                Tables\Columns\TextColumn::make('TipoDocumento')->label('Tipo')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'CONVENIO' => 'Convenio',
                    'CONTRATO' => 'Contrato',
                    'MEMORANDUM' => 'Memorándum',
                    'EVIDENCIA' => 'Evidencia',
                    default => 'Otro',
                }),
                Tables\Columns\TextColumn::make('NombreOriginal')->label('Archivo')->limit(35),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('subidoPor.name')->label('Subido por')->placeholder('Sistema'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Adjuntar documento')->icon('heroicon-o-paper-clip'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->openUrlInNewTab()
                    ->url(fn (TrabajadorArchivo $record): string => route('declaraciones.trabajadores.archivos.ver', $record)),
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (TrabajadorArchivo $record): string => route('declaraciones.trabajadores.archivos.descargar', $record)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
