<?php

namespace App\Services\Rop;

use App\Models\RopPeriodo;
use App\Models\RopRegistro;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RopQualificationService
{
    public function __construct(
        private readonly RopAuditService $audit,
        private readonly RopCompletenessService $completeness,
    ) {}

    public function calificar(RopRegistro $registro, ?string $calificacion, string $motivo, ?string $observaciones = null): RopRegistro
    {
        validator(compact('calificacion', 'motivo'), [
            'calificacion' => ['nullable', Rule::in(array_keys(RopRegistro::calificaciones()))],
            'motivo' => ['required', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($registro, $calificacion, $motivo, $observaciones): RopRegistro {
            $registro = RopRegistro::withoutGlobalScopes()->lockForUpdate()->findOrFail($registro->getKey());
            $periodo = RopPeriodo::withoutGlobalScopes()->findOrFail($registro->RopPeriodoID);

            if ($periodo->Estado === RopPeriodo::ESTADO_CERRADO) {
                throw ValidationException::withMessages(['Calificacion' => 'El período está cerrado y no admite cambios.']);
            }

            $this->completeness->validar($registro);

            $anteriores = $registro->only(['Estado', 'Calificacion', 'MotivoCalificacion', 'ObservacionesCalificacion', 'CalificadoPorID', 'CalificadoEn']);
            $registro->forceFill([
                'Estado' => RopRegistro::ESTADO_CALIFICADO,
                'Calificacion' => $calificacion,
                'MotivoCalificacion' => $motivo,
                'ObservacionesCalificacion' => $observaciones,
                'CalificadoPorID' => auth()->id(),
                'CalificadoEn' => now(),
            ])->save();
            $this->audit->registrar('CALIFICAR', $registro, $anteriores, $registro->only(array_keys($anteriores)), $motivo);

            return $registro->refresh();
        });
    }
}
