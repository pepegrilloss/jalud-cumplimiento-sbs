<?php
namespace App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditDeclaracionTrabajador extends EditRecord
{
    protected static string $resource = DeclaracionTrabajadorResource::class;
    protected function getHeaderActions(): array { return [Actions\ViewAction::make()]; }
    protected function getFormActions(): array { return []; }
}
