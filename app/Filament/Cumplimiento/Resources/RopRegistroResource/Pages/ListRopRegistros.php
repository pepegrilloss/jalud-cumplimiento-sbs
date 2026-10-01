<?php

namespace App\Filament\Cumplimiento\Resources\RopRegistroResource\Pages;

use App\Filament\Cumplimiento\Resources\RopRegistroResource;
use App\Services\Rop\RopDetectionService;
use App\Services\Rop\RopPeriodService;
use Carbon\CarbonImmutable;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;

class ListRopRegistros extends ListRecords
{
    protected static string $resource = RopRegistroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sincronizar')
                ->label('Cargar créditos por fecha')
                ->icon('heroicon-o-calendar-days')
                ->visible(fn (): bool => auth()->user()?->can('sincronizar_rop') ?? false)
                ->form([
                    Forms\Components\DatePicker::make('FechaGeneracion')
                        ->label('Fecha de generación de los créditos')
                        ->default(now())
                        ->required()
                        ->native(false),
                ])
                ->modalHeading('Cargar créditos desde JALUD')
                ->modalSubmitActionLabel('Procesar fecha')
                ->action(function (array $data): void {
                    $fecha = CarbonImmutable::parse($data['FechaGeneracion']);
                    $resultado = app(RopDetectionService::class)->detectar(
                        desde: $fecha,
                        hasta: $fecha,
                        sedeId: auth()->user()?->getEffectiveSedeId(),
                    );
                    Notification::make()->success()->title('Créditos procesados')
                        ->body("Nuevos: {$resultado->creados}. Ya existentes: {$resultado->duplicados}.")->send();
                }),
            Actions\Action::make('cerrar_periodo')
                ->label('Cerrar período mensual')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->can('cerrar_periodo_rop') ?? false)
                ->form([
                    Forms\Components\DatePicker::make('Periodo')->label('Mes a cerrar')->helperText('Primero se sincronizan los créditos que corresponden al mes. No se cerrará mientras existan ROP pendientes de completar o calificar.')
                        ->default(now()->startOfMonth())->required()->native(false),
                ])
                ->modalDescription('El cierre registra fecha y usuario, impide nuevos ingresos al período y bloquea la edición de sus ROP. La operación no se puede revertir desde esta pantalla.')
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $sedeId = (int) auth()->user()?->getEffectiveSedeId();
                    try {
                        $periodo = app(RopPeriodService::class)->cerrar($sedeId, $data['Periodo']);
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()
                            ->title('No se pudo cerrar el período')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Revise los ROP pendientes antes de cerrar.')
                            ->persistent()->send();

                        return;
                    }

                    if ($periodo->yaEstabaCerrado) {
                        Notification::make()->info()
                            ->title('El período ya estaba cerrado')
                            ->body('No se realizaron cambios. Período: '.$periodo->periodo->Periodo->format('m/Y').'.')
                            ->send();

                        return;
                    }

                    if ($periodo->pendientes > 0) {
                        Notification::make()->warning()
                            ->title('El período todavía no se puede cerrar')
                            ->body("Se sincronizaron {$periodo->ropCreadosDuranteCierre} nuevos ROP y quedan {$periodo->pendientes} pendientes. Completa y califica esos registros; luego vuelve a cerrar el período.")
                            ->persistent()->send();

                        return;
                    }

                    Notification::make()->success()
                        ->title('Período ROP cerrado')
                        ->body('Período: '.$periodo->periodo->Periodo->format('m/Y').'. Los ROP del mes quedaron bloqueados para edición y no se admitirán nuevos registros.')
                        ->send();
                }),
        ];
    }
}
