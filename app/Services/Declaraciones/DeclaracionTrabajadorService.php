<?php

namespace App\Services\Declaraciones;

use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Models\DeclaracionTrabajador;
use App\Models\Trabajador;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Rules\DocumentoIdentidadValido;

class DeclaracionTrabajadorService
{
    public const RELACIONES = ['estudios', 'educacionEscolar', 'otrosEstudios', 'experienciasLaborales', 'capacitaciones', 'bienesInmuebles', 'vehiculos', 'otrosBienes', 'inversiones', 'obligaciones'];

    public function datosNuevaFicha(Trabajador $trabajador): array
    {
        if (DeclaracionTrabajador::withoutGlobalScope('sede')->where('TrabajadorID',$trabajador->getKey())->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['TrabajadorID'=>'El trabajador ya tiene una declaración. Use “Crear nueva versión” desde la última declaración finalizada.']);
        }
        return ['SedeID'=>$trabajador->SedeID,'NumeroVersion'=>1,'Estado'=>EstadoDeclaracion::BORRADOR->value];
    }

    public function finalizar(DeclaracionTrabajador $declaracion): DeclaracionTrabajador
    {
        abort_if($declaracion->Estado !== EstadoDeclaracion::BORRADOR->value, 422, 'La declaración ya fue finalizada.');
        $declaracion->sanitizarCondicionales(); $declaracion->save();
        $declaracion->load(self::RELACIONES);
        $this->validar($declaracion);

        return DB::transaction(function () use ($declaracion): DeclaracionTrabajador {
            $declaracion->forceFill([
                'Estado' => EstadoDeclaracion::FINALIZADO->value,
                'FinalizadoPorID' => auth()->id(),
                'FinalizadoEn' => now(),
            ])->save();
            return $declaracion->refresh()->load(self::RELACIONES);
        });
    }

    public function nuevaVersion(DeclaracionTrabajador $origen): DeclaracionTrabajador
    {
        Gate::authorize('nuevaVersion', $origen);
        abort_if($origen->Estado !== EstadoDeclaracion::FINALIZADO->value, 422, 'Solo se versionan declaraciones finalizadas.');

        return DB::transaction(function () use ($origen): DeclaracionTrabajador {
            $origen=DeclaracionTrabajador::withoutGlobalScope('sede')->where('TrabajadorID',$origen->TrabajadorID)->where('Estado',EstadoDeclaracion::FINALIZADO->value)->lockForUpdate()->latest('NumeroVersion')->firstOrFail();
            $origen->load(self::RELACIONES);
            $copia = $origen->replicate();
            $copia->NumeroVersion = (int) DeclaracionTrabajador::withoutGlobalScope('sede')->where('TrabajadorID', $origen->TrabajadorID)->max('NumeroVersion') + 1;
            $copia->Estado = EstadoDeclaracion::BORRADOR->value;
            $copia->CreadoPorID = auth()->id() ?? $origen->CreadoPorID;
            $copia->ActualizadoPorID = auth()->id() ?? $origen->ActualizadoPorID ?? $origen->CreadoPorID;
            $copia->FinalizadoPorID = null;
            $copia->FinalizadoEn = null;
            $copia->save();

            foreach (self::RELACIONES as $relacion) {
                foreach ($origen->{$relacion} as $detalle) {
                    $nuevo = $detalle->replicate();
                    $nuevo->DeclaracionTrabajadorID = $copia->getKey();
                    $nuevo->save();
                }
            }
            return $copia->load(self::RELACIONES);
        });
    }

    public static function experienciasUltimosDosAnios(Collection $experiencias, CarbonInterface $fechaContratacion): Collection
    {
        $desde = $fechaContratacion->copy()->subYears(2)->startOfDay();
        $hasta = $fechaContratacion->copy()->endOfDay();
        return $experiencias->filter(fn ($e) => $e->FechaInicio->lte($hasta) && ($e->FechaConclusion === null || $e->FechaConclusion->gte($desde)))
            ->sortByDesc('FechaInicio')->values();
    }

    private function validar(DeclaracionTrabajador $d): void
    {
        $data = $d->toArray();
        $data['experiencias'] = $d->experienciasLaborales->toArray();
        Validator::make($data, [
            'FechaContratacion' => ['required', 'date'], 'Nombres' => ['required'], 'ApellidoPaterno' => ['required'],
            'TipoDocumento' => ['required'], 'NumeroDocumento' => ['required', new DocumentoIdentidadValido($d->TipoDocumento)], 'EstadoCivil' => ['required'],
            'TipoDocumentoOtro' => [Rule::requiredIf($d->TipoDocumento === 'OTRO')],
            'ConyugeNombres' => [Rule::requiredIf(in_array($d->EstadoCivil, ['CASADO', 'CONVIVIENTE'], true))],
            'ConyugeApellidos' => [Rule::requiredIf(in_array($d->EstadoCivil, ['CASADO', 'CONVIVIENTE'], true))],
            'CodigoOcupacion' => ['required'], 'Cargo' => ['required'], 'DomicilioVia' => ['required'],
            'DomicilioDistrito' => ['required'], 'DomicilioProvincia' => ['required'], 'DomicilioDepartamento' => ['required'],
            'DetalleAntecedentesPoliciales' => [Rule::requiredIf((bool) $d->TieneAntecedentesPoliciales)],
            'DetalleAntecedentesPenales' => [Rule::requiredIf((bool) $d->TieneAntecedentesPenales)],
            'CondicionViviendaOtra' => [Rule::requiredIf($d->CondicionVivienda === 'OTRA')],
            'CiudadSuscripcion' => ['required'], 'FechaDeclaracion' => ['required', 'date'],
            'RemuneracionBrutaMensual' => ['numeric', 'min:0'], 'IngresoEjercicioIndividual' => ['numeric', 'min:0'], 'OtrosIngresosMensuales' => ['numeric', 'min:0'],
            'experiencias.*.FechaInicio' => ['required', 'date'],
            'experiencias.*.FechaConclusion' => ['nullable', 'date', 'after_or_equal:experiencias.*.FechaInicio'],
        ], [], [
            'ConyugeNombres' => 'nombres del cónyuge/conviviente', 'ConyugeApellidos' => 'apellidos del cónyuge/conviviente',
        ])->validate();
    }
}
