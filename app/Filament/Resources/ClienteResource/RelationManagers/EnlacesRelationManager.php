<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use App\Models\EnlaceCliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EnlacesRelationManager extends RelationManager
{
    protected static string $relationship = 'enlaces';

    protected static ?string $title = 'Visitas y enlaces del cliente';

    protected static ?string $recordTitleAttribute = 'NOMBRE';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('NOMBRE')
                    ->label('Visita')
                    ->formatStateUsing(fn (EnlaceCliente $record): string => 'Visita #'.$record->getKey())
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('url_publica')
                    ->label('URL para el cliente')
                    ->state(fn (EnlaceCliente $record): string => $record->urlPublica())
                    ->copyable()
                    ->copyMessage('Enlace copiado')
                    ->limit(48)
                    ->tooltip(fn (EnlaceCliente $record): string => $record->urlPublica()),

                Tables\Columns\TextColumn::make('registros_count')
                    ->label('Personas')
                    ->counts('registros')
                    ->badge()
                    ->color('success'),

                Tables\Columns\ToggleColumn::make('ACTIVO')
                    ->label('Activo'),

                Tables\Columns\IconColumn::make('CERRADO_AT')
                    ->label('Estado')
                    ->boolean()
                    ->state(fn (EnlaceCliente $record): bool => $record->CERRADO_AT !== null)
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('ULTIMO_ACCESO_AT')
                    ->label('Último registro')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin registros'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generar_enlace')
                    ->label('Nueva visita')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->action(function (): void {
                        $cliente = $this->getOwnerRecord();

                        if ($cliente->enlaces()->whereNull('CERRADO_AT')->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Este cliente tiene una visita abierta')
                                ->body('Primero debe cerrarse la visita actual. Luego podrás generar el enlace de la siguiente visita.')
                                ->send();

                            return;
                        }

                        $token = EnlaceCliente::tokenNuevo();
                        $cliente->enlaces()->create(array_merge(EnlaceCliente::atributosToken($token), [
                            'NOMBRE' => 'Registro de visitantes',
                            'ACTIVO' => true,
                            'ID_MP_LGN_USUARIO_CREADOR' => auth()->id(),
                        ]));

                        Notification::make()
                            ->success()
                            ->title('Nueva visita creada')
                            ->body('Comparte el nuevo enlace con el cliente para registrar a sus visitantes.')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('qr')
                    ->label('QR y enlace')
                    ->icon('heroicon-o-qr-code')
                    ->modalHeading('Enlace de registro del cliente')
                    ->modalContent(fn (EnlaceCliente $record) => view('filament.clientes.enlace-qr', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                Tables\Actions\Action::make('regenerar')
                    ->label('Cambiar llave')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('El enlace anterior dejará de funcionar inmediatamente. Los registros existentes se conservarán.')
                    ->action(function (EnlaceCliente $record): void {
                        $record->update(EnlaceCliente::atributosToken(EnlaceCliente::tokenNuevo()));
                        Notification::make()->success()->title('La llave fue renovada')->send();
                    }),
            ])
            ->emptyStateHeading('Este cliente todavía no tiene visitas')
            ->emptyStateDescription('Crea la primera visita para obtener su enlace seguro de registro.')
            ->emptyStateIcon('heroicon-o-link');
    }
}
