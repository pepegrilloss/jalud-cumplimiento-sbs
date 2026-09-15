<?php

namespace App\Filament\Cumplimiento\Resources\RopRegistroResource\Pages;

use App\Filament\Cumplimiento\Resources\RopRegistroResource;
use App\Services\Rop\RopAuditService;
use App\Services\Rop\RopCompletenessService;
use App\Services\Rop\RopOfficialDataService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditRopRegistro extends EditRecord
{
    protected static string $resource = RopRegistroResource::class;

    private array $anteriores = [];

    public function getTitle(): string
    {
        return (app(RopCompletenessService::class)->estaCompleto($this->record) ? 'Editar ' : 'Completar ')
            .$this->getRecordTitle();
    }

    public function getBreadcrumb(): string
    {
        return app(RopCompletenessService::class)->estaCompleto($this->record)
            ? 'Editar'
            : 'Completar';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['DatosRegulatorios'] = app(RopOfficialDataService::class)->datos($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->periodo?->Estado === 'CERRADO') {
            throw ValidationException::withMessages(['DatosRegulatorios' => 'El período mensual está cerrado y el ROP ya no puede modificarse.']);
        }

        $this->anteriores = $this->record->DatosRegulatorios ?? [];

        return $data;
    }

    protected function afterSave(): void
    {
        app(RopAuditService::class)->registrar(
            'COMPLETAR_ROP',
            $this->record,
            ['DatosRegulatorios' => $this->anteriores],
            ['DatosRegulatorios' => $this->record->DatosRegulatorios],
            'Llenado o actualización del formato oficial ROP',
        );
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()->success()->title('Formato ROP guardado');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Ver detalle'),
            Actions\Action::make('excel')->label('Descargar Excel')->icon('heroicon-o-table-cells')
                ->visible(fn (): bool => app(RopCompletenessService::class)->estaCompleto($this->record))
                ->url(fn (): string => route('rop.reporte.excel', $this->record)),
        ];
    }
}
