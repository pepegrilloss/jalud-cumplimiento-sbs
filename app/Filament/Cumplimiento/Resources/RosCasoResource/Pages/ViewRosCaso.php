<?php

namespace App\Filament\Cumplimiento\Resources\RosCasoResource\Pages;

use App\Filament\Cumplimiento\Resources\RosCasoResource;
use App\Filament\Cumplimiento\Resources\RosCasoResource\Pages\Concerns\HasRosWorkflowActions;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRosCaso extends ViewRecord
{
    use HasRosWorkflowActions;

    protected static string $resource = RosCasoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn (): bool => RosCasoResource::canEdit($this->record)),
            Actions\Action::make('pdf')
                ->label('Vista previa PDF')
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => route('ros.reporte.pdf', $this->record))
                ->openUrlInNewTab(),
            ...$this->rosWorkflowActions(),
        ];
    }
}
