<?php

namespace App\Filament\Cumplimiento\Resources\RosCasoResource\Pages\Concerns;

use App\Filament\Cumplimiento\Resources\RosCasoResource;
use App\Models\RosCaso;
use App\Services\Ros\RosWorkflowService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;

trait HasRosWorkflowActions
{
    protected function rosWorkflowActions(): array
    {
        return [
            Actions\Action::make('pdf')
                ->label('Vista previa PDF (interna)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn (): string => route('ros.reporte.pdf', $this->record)),
            Actions\Action::make('excel')
                ->label('Descargar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => route('ros.reporte.excel', $this->record)),
            Actions\Action::make('enviar_evaluacion')
                ->label('Enviar a evaluación')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(fn (): bool => $this->puede(RosCaso::ESTADO_EVALUACION))
                ->requiresConfirmation()
                ->action(fn () => $this->transicionar(RosCaso::ESTADO_EVALUACION)),
            Actions\Action::make('volver_borrador')
                ->label('Volver a borrador')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(fn (): bool => $this->puede(RosCaso::ESTADO_BORRADOR))
                ->form([Forms\Components\Textarea::make('motivo')->label('Motivo')->required()])
                ->action(fn (array $data) => $this->transicionar(RosCaso::ESTADO_BORRADOR, $data['motivo'])),
            Actions\Action::make('descartar')
                ->label('Descartar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->puede(RosCaso::ESTADO_DESCARTADO))
                ->form([Forms\Components\Textarea::make('motivo')->label('Fundamento del descarte')->required()->rows(5)])
                ->action(fn (array $data) => $this->transicionar(RosCaso::ESTADO_DESCARTADO, $data['motivo'])),
            Actions\Action::make('aprobar')
                ->label('Aprobar para ROS')
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->visible(fn (): bool => $this->puede(RosCaso::ESTADO_APROBADO))
                ->requiresConfirmation()
                ->modalDescription('Se validará que el expediente tenga identificación, operaciones, análisis, señales y datos del reporte.')
                ->action(fn () => $this->transicionar(RosCaso::ESTADO_APROBADO)),
            Actions\Action::make('registrar_envio')
                ->label('Registrar envío ROSEL')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => $this->puede(RosCaso::ESTADO_REPORTADO))
                ->form([
                    Forms\Components\TextInput::make('NumeroEnvioRosel')->label('Número o constancia ROSEL')->required(),
                    Forms\Components\DatePicker::make('FechaEnvioRosel')->label('Fecha de envío')->default(now())->native(false)->required(),
                    Forms\Components\FileUpload::make('ConstanciaRoselRuta')
                        ->label('Constancia de envío')
                        ->disk('local')
                        ->directory('cumplimiento-sbs/constancias-rosel')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240),
                    Forms\Components\Textarea::make('motivo')->label('Observación del envío'),
                ])
                ->action(function (array $data): void {
                    $motivo = $data['motivo'] ?? null;
                    unset($data['motivo']);
                    $this->transicionar(RosCaso::ESTADO_REPORTADO, $motivo, $data);
                }),
        ];
    }

    private function puede(string $estado): bool
    {
        return app(RosWorkflowService::class)->puedeTransicionar($this->record, $estado);
    }

    private function transicionar(string $estado, ?string $motivo = null, array $datos = []): void
    {
        $caso = app(RosWorkflowService::class)->transicionar($this->record, $estado, $motivo, $datos);
        Notification::make()->success()->title('Estado actualizado')->body(RosCaso::estados()[$estado] ?? $estado)->send();
        $this->redirect(RosCasoResource::getUrl('view', ['record' => $caso]));
    }
}
