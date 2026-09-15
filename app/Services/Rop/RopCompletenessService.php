<?php

namespace App\Services\Rop;

use App\Models\RopRegistro;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RopCompletenessService
{
    public function estaCompleto(RopRegistro $registro): bool
    {
        return ! $this->validator($registro)->fails();
    }

    public function validar(RopRegistro $registro): void
    {
        $validator = $this->validator($registro);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'DatosRegulatorios' => 'Debe completar y guardar todos los datos obligatorios del ROP antes de continuar.',
            ]);
        }
    }

    private function validator(RopRegistro $registro): \Illuminate\Contracts\Validation\Validator
    {
        $datos = $registro->DatosRegulatorios;

        return Validator::make([
            'NumeroRop' => $registro->NumeroRop,
            'FechaOperacion' => $registro->FechaOperacion,
            'ClienteNombre' => $registro->ClienteNombre,
            'CodigoCredito' => $registro->CodigoCredito,
            'Monto' => $registro->Monto,
            'Moneda' => $registro->Moneda,
            'DatosRegulatorios' => $datos,
        ], [
            'NumeroRop' => ['required'],
            'FechaOperacion' => ['required', 'date'],
            'ClienteNombre' => ['required'],
            'CodigoCredito' => ['required'],
            'Monto' => ['required', 'numeric'],
            'Moneda' => ['required'],
            'DatosRegulatorios' => ['required', 'array'],
            'DatosRegulatorios.organismo_supervisor' => ['required'],
            'DatosRegulatorios.codigo_sujeto_obligado' => ['required'],
            'DatosRegulatorios.codigo_oficial_cumplimiento' => ['required'],
            'DatosRegulatorios.tipo_sujeto_obligado' => ['required'],
            'DatosRegulatorios.tipos_operacion' => ['required', 'array', 'min:1'],
            'DatosRegulatorios.individual_multiple' => ['required', Rule::in(['INDIVIDUAL', 'MULTIPLE'])],
            'DatosRegulatorios.tipo_persona' => ['required', Rule::in(['NATURAL', 'JURIDICA'])],
            'DatosRegulatorios.persona_natural.apellido_paterno' => ['required_if:DatosRegulatorios.tipo_persona,NATURAL'],
            'DatosRegulatorios.persona_natural.apellido_materno' => ['required_if:DatosRegulatorios.tipo_persona,NATURAL'],
            'DatosRegulatorios.persona_natural.nombres' => ['required_if:DatosRegulatorios.tipo_persona,NATURAL'],
            'DatosRegulatorios.persona_natural.tipo_documento' => ['required_if:DatosRegulatorios.tipo_persona,NATURAL'],
            'DatosRegulatorios.persona_natural.numero_documento' => ['required_if:DatosRegulatorios.tipo_persona,NATURAL'],
            'DatosRegulatorios.persona_juridica.razon_social' => ['required_if:DatosRegulatorios.tipo_persona,JURIDICA'],
            'DatosRegulatorios.persona_juridica.ruc' => ['required_if:DatosRegulatorios.tipo_persona,JURIDICA'],
        ]);
    }
}
