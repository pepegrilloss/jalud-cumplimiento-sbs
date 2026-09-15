<?php

namespace App\Filament\Cumplimiento\Resources\RosCasoResource\Pages;

use App\Filament\Cumplimiento\Resources\RosCasoResource;
use App\Models\RosCaso;
use App\Services\Ros\RosPrefillService;
use Filament\Resources\Pages\CreateRecord;

class CreateRosCaso extends CreateRecord
{
    protected static string $resource = RosCasoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['RopRegistroID'] = null;
        $data['OrigenRos'] = RosCaso::ORIGEN_DIRECTO;
        if (($data['TipoPersonaOrigen'] ?? RosCaso::PERSONA_CLIENTE) === RosCaso::PERSONA_TRABAJADOR) {
            $data['ClienteID'] = null;
            $data['CreditoID'] = null;
            $data['PagoID'] = null;
            $data['ZonaID'] = null;
        } else {
            $data['TrabajadorID'] = null;
        }
        $sedeId = (int) ($data['SedeID'] ?? auth()->user()?->getEffectiveSedeId());
        $configuracion = app(RosPrefillService::class)->configuracion($sedeId);
        $mapeo = ['Departamento' => 'DepartamentoOperacion', 'Provincia' => 'ProvinciaOperacion', 'Distrito' => 'DistritoOperacion'];

        foreach ($configuracion as $campo => $valor) {
            $destino = $mapeo[$campo] ?? $campo;
            $data[$destino] = $data[$destino] ?? $valor;
        }

        return $data;
    }
}
