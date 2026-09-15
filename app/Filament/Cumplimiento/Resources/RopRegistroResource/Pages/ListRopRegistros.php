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
                    Forms\Components\DatePicker::make('Periodo')->label('Mes a cerrar')->default(now()->startOfMonth())->required()->native(false),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $sedeId = (int) auth()->user()?->getEffectiveSedeId();
                    app(RopPeriodService::class)->cerrar($sedeId, $data['Periodo']);
                    Notification::make()->success()->title('Período ROP cerrado')->send();
                }),
        ];
    }
}
