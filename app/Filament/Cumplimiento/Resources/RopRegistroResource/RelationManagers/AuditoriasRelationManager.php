<?php

namespace App\Filament\Cumplimiento\Resources\RopRegistroResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditoriasRelationManager extends RelationManager
{
    protected static string $relationship = 'auditorias';

    protected static ?string $title = '8. Auditoría';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Fecha y hora')->dateTime('d/m/Y H:i:s'),
            Tables\Columns\TextColumn::make('usuario.name')->label('Usuario')->placeholder('Sistema automático'),
            Tables\Columns\BadgeColumn::make('Accion')->label('Acción'),
            Tables\Columns\TextColumn::make('Motivo')->wrap()->placeholder('—'),
        ])->actions([
            Tables\Actions\Action::make('detalle')->label('Detalle')->icon('heroicon-o-eye')
                ->modalSubmitAction(false)->modalCancelActionLabel('Cerrar')->form([
                    Forms\Components\KeyValue::make('ValoresAnteriores')->label('Valores anteriores')->disabled(),
                    Forms\Components\KeyValue::make('ValoresNuevos')->label('Valores nuevos')->disabled(),
                ]),
        ])->headerActions([])->bulkActions([]);
    }
}
