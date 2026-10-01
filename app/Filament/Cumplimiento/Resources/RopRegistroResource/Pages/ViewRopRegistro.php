<?php

namespace App\Filament\Cumplimiento\Resources\RopRegistroResource\Pages;

use App\Filament\Cumplimiento\Resources\RopRegistroResource;
use App\Filament\Cumplimiento\Resources\RosCasoResource;
use App\Models\RopRegistro;
use App\Services\Rop\RopCompletenessService;
use App\Services\Rop\RopQualificationService;
use App\Services\Rop\RopToRosService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRopRegistro extends ViewRecord
{
    protected static string $resource = RopRegistroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(fn (): string => app(RopCompletenessService::class)->estaCompleto($this->record)
                    ? 'Editar ROP'
                    : 'Completar ROP')
                ->icon(fn (): string => app(RopCompletenessService::class)->estaCompleto($this->record)
                    ? 'heroicon-o-pencil-square'
                    : 'heroicon-o-document-plus')
                ->visible(fn (): bool => (auth()->user()?->can('completar_rop') ?? false) && $this->record->periodo?->Estado !== 'CERRADO'),
            Actions\Action::make('revisar_calificar')
                ->label(fn (): string => filled($this->record->CalificadoEn)
                    ? 'Editar calificación'
                    : 'Revisar/Calificar')
                ->icon(fn (): string => filled($this->record->CalificadoEn)
                    ? 'heroicon-o-pencil-square'
                    : 'heroicon-o-clipboard-document-check')
                ->visible(fn (): bool => (auth()->user()?->can('calificar_rop') ?? false)
                    && app(RopCompletenessService::class)->estaCompleto($this->record)
                    && $this->record->periodo?->Estado !== 'CERRADO')
                ->form(RopRegistroResource::calificacionForm($this->record))
                ->fillForm(fn (): array => [
                    'ResultadoRevision' => $this->record->Calificacion ?? 'SIN_CLASIFICACION',
                    'MotivoCalificacion' => $this->record->MotivoCalificacion,
                    'ObservacionesCalificacion' => $this->record->ObservacionesCalificacion,
                ])
                ->action(function (array $data): void {
                    $this->record = app(RopQualificationService::class)->calificar(
                        $this->record,
                        $data['ResultadoRevision'] === 'SIN_CLASIFICACION' ? null : $data['ResultadoRevision'],
                        $data['MotivoCalificacion'],
                        $data['ObservacionesCalificacion'] ?? null,
                    );
                    Notification::make()->success()->title('ROP revisado y calificado')->send();
                }),
            Actions\Action::make('excel')->label('Descargar Excel')->icon('heroicon-o-table-cells')
                ->visible(fn (): bool => app(RopCompletenessService::class)->estaCompleto($this->record))
                ->url(fn (): string => route('rop.reporte.excel', $this->record)),
            Actions\Action::make('pdf')->label('Vista previa PDF')->icon('heroicon-o-document-text')
                ->url(fn (): string => route('rop.reporte.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('generar_ros')->label('Generar ROS')->icon('heroicon-o-shield-exclamation')->color('danger')
                ->visible(fn (): bool => (auth()->user()?->can('generar_ros_desde_rop') ?? false)
                    && $this->record->Calificacion === RopRegistro::CALIFICACION_SOSPECHOSA
                    && blank($this->record->RosCasoID))
                ->requiresConfirmation()
                ->action(function () {
                    $ros = app(RopToRosService::class)->generar($this->record);

                    return redirect()->to(RosCasoResource::getUrl('edit', ['record' => $ros]));
                }),
        ];
    }
}
