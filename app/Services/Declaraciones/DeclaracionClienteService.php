<?php

namespace App\Services\Declaraciones;

use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Models\DeclaracionCliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use App\Rules\DocumentoIdentidadValido;

class DeclaracionClienteService
{
    public const RELACIONES = ['pepFamiliares', 'pepRelacionados'];

    public function finalizar(DeclaracionCliente $declaracion): DeclaracionCliente
    {
        abort_if($declaracion->Estado !== EstadoDeclaracion::BORRADOR->value, 422, 'La declaración ya fue finalizada.');
        $this->sanitizarRelaciones($declaracion);
        $declaracion->sanitizarCondicionales(); $declaracion->save();
        $declaracion->load(self::RELACIONES);
        $this->validar($declaracion);
        return DB::transaction(function () use ($declaracion): DeclaracionCliente {
            $declaracion->forceFill(['Estado' => EstadoDeclaracion::FINALIZADO->value, 'FinalizadoPorID' => auth()->id(), 'FinalizadoEn' => now()])->save();
            return $declaracion->refresh()->load(self::RELACIONES);
        });
    }

    public function nuevaVersion(DeclaracionCliente $origen): DeclaracionCliente
    {
        Gate::authorize('nuevaVersion', $origen);
        abort_if($origen->Estado !== EstadoDeclaracion::FINALIZADO->value, 422, 'Solo se versionan declaraciones finalizadas.');
        return DB::transaction(function () use ($origen): DeclaracionCliente {
            $origen=DeclaracionCliente::withoutGlobalScope('sede')->where('ClienteID',$origen->ClienteID)->where('Estado',EstadoDeclaracion::FINALIZADO->value)->lockForUpdate()->latest('NumeroVersion')->firstOrFail();
            $origen->load(self::RELACIONES);
            $copia = $origen->replicate();
            $copia->NumeroVersion = (int) DeclaracionCliente::withoutGlobalScope('sede')->where('ClienteID', $origen->ClienteID)->max('NumeroVersion') + 1;
            $copia->forceFill(['Estado' => EstadoDeclaracion::BORRADOR->value, 'CreadoPorID' => auth()->id() ?? $origen->CreadoPorID, 'ActualizadoPorID' => auth()->id() ?? $origen->ActualizadoPorID ?? $origen->CreadoPorID, 'FinalizadoPorID' => null, 'FinalizadoEn' => null])->save();
            foreach (self::RELACIONES as $relacion) {
                foreach ($origen->{$relacion} as $detalle) {
                    $nuevo = $detalle->replicate(); $nuevo->DeclaracionClienteID = $copia->getKey(); $nuevo->save();
                }
            }
            return $copia->load(self::RELACIONES);
        });
    }

    private function validar(DeclaracionCliente $d): void
    {
        $esPep = in_array($d->EstadoPep, ['SI_SOY', 'SI_HE_SIDO'], true);
        $terceroPep = in_array($d->TerceroEstadoPep, ['SI_ES', 'SI_HA_SIDO'], true);
        Validator::make($d->toArray(), [
            'ClienteID' => ['required'], 'Nombres' => ['required'], 'Apellidos' => ['required'], 'TipoDocumento' => ['required'],
            'NumeroDocumento' => ['required', new DocumentoIdentidadValido($d->TipoDocumento)], 'TipoDocumentoOtro' => [Rule::requiredIf($d->TipoDocumento === 'OTRO')],
            'EstadoCivil' => ['required', Rule::in(['SOLTERO','CASADO','VIUDO','DIVORCIADO'])], 'DomicilioVia' => ['required'],
            'ConyugeConviviente' => [Rule::requiredIf($d->EstadoCivil === 'CASADO')],
            'DomicilioDistrito' => ['required'], 'DomicilioProvincia' => ['required'], 'DomicilioDepartamento' => ['required'],
            'Ocupacion' => ['required'], 'Correo' => ['nullable', 'email'], 'PropositoRelacion' => ['required'],
            'EstadoPep' => ['required'], 'EstadoColaboradorAutoridad' => ['required'],
            'PepCargo' => [Rule::requiredIf($esPep)], 'PepInstitucion' => [Rule::requiredIf($esPep)],
            'TipoBeneficiario' => ['required'],
            'OrigenFondos' => ['required'],
            'TerceroNombresApellidos' => [Rule::requiredIf($d->TipoBeneficiario === 'TERCERO_NATURAL')],
            'TerceroTipoDocumento' => [Rule::excludeIf($d->TipoBeneficiario !== 'TERCERO_NATURAL'), 'required'],
            'TerceroTipoDocumentoOtro' => [Rule::excludeIf($d->TipoBeneficiario !== 'TERCERO_NATURAL' || $d->TerceroTipoDocumento !== 'OTRO'), 'required'],
            'TerceroNumeroDocumento' => [Rule::excludeIf($d->TipoBeneficiario !== 'TERCERO_NATURAL'), 'required', new DocumentoIdentidadValido($d->TerceroTipoDocumento)],
            'TerceroRepresentacion' => [Rule::requiredIf($d->TipoBeneficiario === 'TERCERO_NATURAL')],
            'TerceroPepCargo' => [Rule::requiredIf($d->TipoBeneficiario === 'TERCERO_NATURAL' && $terceroPep)],
            'TerceroPepInstitucion' => [Rule::requiredIf($d->TipoBeneficiario === 'TERCERO_NATURAL' && $terceroPep)],
            'EntidadRazonSocial' => [Rule::requiredIf(in_array($d->TipoBeneficiario, ['PERSONA_JURIDICA', 'ENTE_JURIDICO'], true))],
            'EntidadRucRegistro' => [Rule::requiredIf(in_array($d->TipoBeneficiario, ['PERSONA_JURIDICA', 'ENTE_JURIDICO'], true))],
            'EntidadRepresentacion' => [Rule::requiredIf(in_array($d->TipoBeneficiario, ['PERSONA_JURIDICA', 'ENTE_JURIDICO'], true))],
            'BeneficiarioFinalNombres' => [Rule::requiredIf(in_array($d->TipoBeneficiario, ['PERSONA_JURIDICA', 'ENTE_JURIDICO'], true))],
            'FechaDeclaracion' => ['required', 'date'],
        ])->validate();
    }

    public function sanitizarRelaciones(DeclaracionCliente $declaracion): void
    {
        if(!in_array($declaracion->EstadoPep,['SI_SOY','SI_HE_SIDO'],true)){$declaracion->pepFamiliares()->delete();}
        if(!$declaracion->EsParientePep){$declaracion->pepRelacionados()->delete();}
    }
}
