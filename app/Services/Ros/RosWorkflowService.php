<?php

namespace App\Services\Ros;

use App\Models\RosCaso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RosWorkflowService
{
    private const TRANSICIONES = [
        RosCaso::ESTADO_BORRADOR => [RosCaso::ESTADO_EVALUACION],
        RosCaso::ESTADO_EVALUACION => [RosCaso::ESTADO_BORRADOR, RosCaso::ESTADO_DESCARTADO, RosCaso::ESTADO_APROBADO],
        RosCaso::ESTADO_DESCARTADO => [RosCaso::ESTADO_EVALUACION],
        RosCaso::ESTADO_APROBADO => [RosCaso::ESTADO_EVALUACION, RosCaso::ESTADO_REPORTADO],
        RosCaso::ESTADO_REPORTADO => [],
    ];

    public function puedeTransicionar(RosCaso $caso, string $destino): bool
    {
        return in_array($destino, self::TRANSICIONES[$caso->Estado] ?? [], true);
    }

    public function transicionar(RosCaso $caso, string $destino, ?string $motivo = null, array $datos = []): RosCaso
    {
        if (!$this->puedeTransicionar($caso, $destino)) {
            throw ValidationException::withMessages([
                'Estado' => "No se permite pasar de {$caso->Estado} a {$destino}.",
            ]);
        }

        $this->validar($caso, $destino, $motivo, $datos);

        return DB::transaction(function () use ($caso, $destino, $motivo, $datos): RosCaso {
            $caso->refresh();
            $origen = $caso->Estado;

            if (!$this->puedeTransicionar($caso, $destino)) {
                throw ValidationException::withMessages(['Estado' => 'El caso cambió de estado. Actualice la página.']);
            }

            $caso->permiteCambioDeEstado = true;
            $caso->fill($datos);
            $caso->Estado = $destino;

            if ($destino === RosCaso::ESTADO_APROBADO) {
                $caso->FechaCalificadoRos = now();
                $caso->CalificadoPorID = auth()->id();
            }

            if ($destino === RosCaso::ESTADO_DESCARTADO) {
                $caso->MotivoDescarte = $motivo;
            }

            if ($destino === RosCaso::ESTADO_REPORTADO) {
                $caso->FechaReportado = now();
                $caso->Bloqueado = true;
            }

            $caso->save();

            $caso->transiciones()->create([
                'SedeID' => $caso->SedeID,
                'UserID' => auth()->id(),
                'EstadoAnterior' => $origen,
                'EstadoNuevo' => $destino,
                'Motivo' => $motivo,
                'FechaTransicion' => now(),
            ]);

            return $caso->refresh();
        });
    }

    private function validar(RosCaso $caso, string $destino, ?string $motivo, array $datos): void
    {
        $errores = [];

        if (in_array($destino, [RosCaso::ESTADO_EVALUACION, RosCaso::ESTADO_APROBADO], true)) {
            $errores += $this->camposRequeridos($caso, [
                'DescripcionHechos' => 'Describa cronológicamente los hechos.',
            ]);

            if (!$caso->personas()->exists()) {
                $errores['personas'] = 'Registre al menos una persona relacionada.';
            }

            if (!$caso->operaciones()->exists()) {
                $errores['operaciones'] = 'Registre al menos una operación.';
            }
        }

        if ($destino === RosCaso::ESTADO_APROBADO) {
            $errores += $this->camposRequeridos($caso, [
                'NumeroReporte' => 'Asigne el número correlativo del reporte.',
                'FechaReporte' => 'Registre la fecha del reporte.',
                'TipoSujetoObligado' => 'Seleccione el tipo de sujeto obligado.',
                'CodigoEmpresa' => 'Registre el código de la empresa.',
                'CodigoOficialCumplimiento' => 'Registre el código del oficial de cumplimiento.',
                'OficinaInformante' => 'Registre la oficina que informó internamente.',
                'OficinaOperacion' => 'Registre la oficina donde ocurrió la operación.',
                'DepartamentoOperacion' => 'Registre el departamento de la operación.',
                'ProvinciaOperacion' => 'Registre la provincia de la operación.',
                'DistritoOperacion' => 'Registre el distrito de la operación.',
                'MontoTotal' => 'Registre el monto total involucrado.',
                'FechaOperacionDesde' => 'Registre la fecha inicial de la operación.',
                'SectorEconomico' => 'Seleccione el sector económico.',
                'ActividadEconomica' => 'Seleccione la actividad económica.',
            ]);

            if (!$caso->senalesAlerta()->exists()) {
                $errores['senalesAlerta'] = 'Registre al menos una señal de alerta.';
            }
        }

        if ($destino === RosCaso::ESTADO_DESCARTADO && blank($motivo)) {
            $errores['motivo'] = 'Explique por qué el caso se descarta.';
        }

        if ($destino === RosCaso::ESTADO_REPORTADO) {
            if (blank($datos['NumeroEnvioRosel'] ?? null)) {
                $errores['NumeroEnvioRosel'] = 'Registre el número o constancia de envío ROSEL.';
            }
            if (blank($datos['FechaEnvioRosel'] ?? null)) {
                $errores['FechaEnvioRosel'] = 'Registre la fecha de envío ROSEL.';
            }
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function camposRequeridos(RosCaso $caso, array $campos): array
    {
        $errores = [];

        foreach ($campos as $campo => $mensaje) {
            if (blank($caso->{$campo})) {
                $errores[$campo] = $mensaje;
            }
        }

        return $errores;
    }
}
