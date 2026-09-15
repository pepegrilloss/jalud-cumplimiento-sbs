<?php

namespace App\Filament\Cumplimiento\Resources\RosCasoResource\Pages;

use App\Filament\Cumplimiento\Resources\RosCasoResource;
use App\Filament\Cumplimiento\Resources\RosCasoResource\Pages\Concerns\HasRosWorkflowActions;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRosCaso extends EditRecord
{
    use HasRosWorkflowActions;

    protected static string $resource = RosCasoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), ...$this->rosWorkflowActions()];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        abort_if($this->record->Bloqueado, 422, 'El caso reportado está bloqueado para edición.');
    }
}
