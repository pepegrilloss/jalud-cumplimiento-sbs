<?php

namespace App\Services\Rop;

use App\Models\RopAuditoria;
use App\Models\RopPeriodo;
use App\Models\RopRegistro;

class RopAuditService
{
    public function registrar(
        string $accion,
        RopRegistro|RopPeriodo $modelo,
        array $anteriores = [],
        array $nuevos = [],
        ?string $motivo = null,
    ): RopAuditoria {
        return RopAuditoria::create([
            'RopRegistroID' => $modelo instanceof RopRegistro ? $modelo->getKey() : null,
            'RopPeriodoID' => $modelo instanceof RopPeriodo ? $modelo->getKey() : ($modelo->RopPeriodoID ?? null),
            'SedeID' => $modelo->SedeID,
            'UserID' => auth()->id(),
            'Accion' => $accion,
            'ValoresAnteriores' => $anteriores ?: null,
            'ValoresNuevos' => $nuevos ?: null,
            'Motivo' => $motivo,
            'IpAddress' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
