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
        private readonly RopCompletenessService $completeness,
    ) {}

    public function cerrar(int $sedeId, string $periodo): RopPeriodCloseResult
    {
        $mes = CarbonImmutable::parse($periodo)->startOfMonth();
        $consultaPeriodo = fn () => RopPeriodo::withoutGlobalScopes()
            ->where('SedeID', $sedeId)
            ->whereDate('Periodo', $mes->toDateString());

        $periodoExistente = $consultaPeriodo()->first();

        if ($periodoExistente?->Estado === RopPeriodo::ESTADO_CERRADO) {
            return new RopPeriodCloseResult($periodoExistente, 0, 0, true);
        }

        // La sincronización se confirma aunque el cierre se rechace por pendientes, para que
        // esos ROP aparezcan en la bandeja y se puedan completar antes de volver a cerrar.
        $deteccion = $this->detector->detectar($mes, $mes->endOfMonth(), $sedeId);

        if ($deteccion->sedesSinConfiguracion > 0) {
            throw ValidationException::withMessages(['Periodo' => 'No se puede cerrar: configure primero el umbral ROP de esta sede.']);
        }

        return DB::transaction(function () use ($sedeId, $mes, $consultaPeriodo, $deteccion): RopPeriodCloseResult {
            $registroPeriodo = $consultaPeriodo()->firstOrCreate(
                ['SedeID' => $sedeId, 'Periodo' => $mes->toDateString()],
                ['Estado' => RopPeriodo::ESTADO_ABIERTO],
            );
            $registroPeriodo = RopPeriodo::withoutGlobalScopes()
                ->whereKey($registroPeriodo->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($registroPeriodo->Estado === RopPeriodo::ESTADO_CERRADO) {
                return new RopPeriodCloseResult($registroPeriodo, 0, $deteccion->creados, true);
            }

            $registrosPeriodo = RopRegistro::withoutGlobalScopes()
                ->where('RopPeriodoID', $registroPeriodo->RopPeriodoID)
                ->get();
            $pendientes = $registrosPeriodo->filter(fn (RopRegistro $registro): bool =>
                $registro->Estado === RopRegistro::ESTADO_PENDIENTE
                || ! $this->completeness->estaCompleto($registro)
            )->count();

            if ($pendientes > 0) {
                return new RopPeriodCloseResult($registroPeriodo, $pendientes, $deteccion->creados);
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

            return new RopPeriodCloseResult($registroPeriodo->refresh(), 0, $deteccion->creados);
        });
    }
}
