<?php
namespace App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource;
use App\Models\DeclaracionTrabajador;
use App\Models\Trabajador;
use Filament\Resources\Pages\CreateRecord;
use App\Services\Declaraciones\DeclaracionTrabajadorService;
class CreateDeclaracionTrabajador extends CreateRecord
{
    protected static string $resource = DeclaracionTrabajadorResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $t = Trabajador::query()->findOrFail($data['TrabajadorID']);
        return array_replace($data, app(DeclaracionTrabajadorService::class)->datosNuevaFicha($t));
    }
    protected function getFormActions(): array { return []; }
}
