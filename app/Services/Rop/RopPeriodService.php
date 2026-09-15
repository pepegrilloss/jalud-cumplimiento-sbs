<?php

namespace App\Services\Rop;

use App\Models\RopPeriodo;
use App\Models\RopRegistro;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RopPeriodService
{
    public function __construct(
        private readonly RopDetectionService $detector,
        private readonly RopAuditService $audit,
    ) {}

    public function cerrar(int $sedeId, string $periodo): RopPeriodo
    {
        $mes = CarbonImmutable::parse($periodo)->startOfMonth();
        $this->detector->detectar($mes, $mes->endOfMonth(), $sedeId);

        return DB::transaction(function () use ($sedeId, $mes): RopPeriodo {
            $registroPeriodo = RopPeriodo::withoutGlobalScopes()
                ->where('SedeID', $sedeId)
                ->whereDate('Periodo', $mes->toDateString())
                ->lockForUpdate()
                ->first();
            $registroPeriodo ??= RopPeriodo::withoutGlobalScopes()->create([
                'SedeID' => $sedeId,
                'Periodo' => $mes->toDateString(),
                'Estado' => RopPeriodo::ESTADO_ABIERTO,
            ]);

            if ($registroPeriodo->Estado === RopPeriodo::ESTADO_CERRADO) {
                return $registroPeriodo;
            }

            $pendientes = RopRegistro::withoutGlobalScopes()
                ->where('RopPeriodoID', $registroPeriodo->RopPeriodoID)
                ->where('Estado', RopRegistro::ESTADO_PENDIENTE)
                ->count();

            if ($pendientes > 0) {
                throw ValidationException::withMessages(['Periodo' => "No se puede cerrar: quedan {$pendientes} ROP pendientes de calificación."]);
            }

            $anteriores = $registroPeriodo->only(['Estado', 'CerradoPorID', 'CerradoEn']);
            $registroPeriodo->forceFill([
                'Estado' => RopPeriodo::ESTADO_CERRADO,
                'CerradoPorID' => auth()->id(),
                'CerradoEn' => now(),
            ])->save();
            $this->audit->registrar('CERRAR_PERIODO', $registroPeriodo, $anteriores, $registroPeriodo->only(array_keys($anteriores)));
            RopRegistro::withoutGlobalScopes()
                ->where('RopPeriodoID', $registroPeriodo->RopPeriodoID)
                ->eachById(fn (RopRegistro $registro) => $this->audit->registrar(
                    'CERRAR_PERIODO',
                    $registro,
                    nuevos: ['Periodo' => $mes->format('Y-m'), 'EstadoPeriodo' => RopPeriodo::ESTADO_CERRADO],
                ), 200, 'RopRegistroID');

            return $registroPeriodo->refresh();
        });
    }
}
