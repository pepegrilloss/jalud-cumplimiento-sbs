<?php

namespace App\Services\Rop;

use App\Models\RopCatalogoItem;
use App\Models\RopRegistro;

class RopOfficialDataService
{
    public function datos(RopRegistro $registro): array
    {
        $snapshot = $registro->SnapshotDatos ?? [];
        $config = data_get($snapshot, 'configuracion', []);
        $cliente = data_get($snapshot, 'cliente', []);
        $operacion = data_get($snapshot, 'operacion', []);

        $base = [
            'organismo_supervisor' => $config['EnteSupervisor'] ?? null,
            'codigo_sujeto_obligado' => $config['CodigoEmpresa'] ?? 'S/C',
            'codigo_oficial_cumplimiento' => $config['CodigoOficialCumplimiento'] ?? 'S/C',
            'tipo_sujeto_obligado' => $this->codigoCatalogo(RopCatalogoItem::TIPO_SUJETO_OBLIGADO, $config['TipoSujetoObligado'] ?? null),
            'tipo_cambio' => $operacion['TipoCambio'] ?? null,
            'moneda_otro' => null,
            'formas_pago' => $operacion['FormaPagoDesembolso'] ?? [],
            'forma_pago_otro' => null,
            'tipos_operacion' => ['OTRO'],
            'tipo_operacion_otro' => $operacion['TipoOperacionDetalle'] ?? 'PRÉSTAMO',
            'compra_venta' => null,
            'tipo_acto_contrato' => null,
            'tipo_servicio' => null,
            'individual_multiple' => $operacion['IndividualMultiple'] ?? 'INDIVIDUAL',
            'oportunidad_pago' => $operacion['OportunidadPago'] ?? null,
            'oportunidad_pago_otro' => null,
            'informacion_adicional' => $operacion['InformacionAdicional'] ?? null,
            'tipo_persona' => $cliente['TipoPersona'] ?? 'NATURAL',
            'persona_natural' => [
                'apellido_paterno' => $cliente['ApellidoPaterno'] ?? null,
                'apellido_materno' => $cliente['ApellidoMaterno'] ?? null,
                'nombres' => $cliente['Nombres'] ?? null,
                'fecha_nacimiento' => $cliente['FechaNacimiento'] ?? null,
                'nacionalidad' => $cliente['Nacionalidad'] ?? null,
                'profesion_ocupacion' => $cliente['ProfesionOcupacion'] ?? $cliente['ActividadEconomica'] ?? null,
                'tipo_documento' => $cliente['TipoDocumento'] ?? 'DNI',
                'numero_documento' => $cliente['NumeroDocumento'] ?? null,
                'pais_emision_documento' => $cliente['PaisEmisionDocumento'] ?? 'PERÚ',
                'departamento' => $cliente['Departamento'] ?? null,
                'provincia' => $cliente['Provincia'] ?? null,
                'distrito' => $cliente['Distrito'] ?? null,
                'urbanizacion_sector' => $cliente['UrbanizacionSector'] ?? null,
                'tipo_via' => null,
                'nombre_via' => $cliente['NombreVia'] ?? $cliente['Domicilio'] ?? null,
                'numero_via' => $cliente['NumeroDomicilio'] ?? null,
                'manzana_lote' => null,
                'telefono_1' => $cliente['Telefono1'] ?? null,
                'telefono_2' => $cliente['Telefono2'] ?? null,
                'estado_civil' => $cliente['EstadoCivil'] ?? null,
                'conyuge' => $cliente['Conyuge'] ?? null,
            ],
            'persona_juridica' => [
                'razon_social' => $cliente['RazonSocial'] ?? $cliente['NombresApellidos'] ?? null,
                'ruc' => $cliente['NumeroDocumento'] ?? null,
                'actividad_economica' => $this->codigoCatalogo(RopCatalogoItem::ACTIVIDAD_ECONOMICA, $cliente['ActividadEconomica'] ?? null),
                'domicilio_extranjero' => null,
                'pais' => null,
                'distrito_estado_extranjero' => null,
                'departamento' => $cliente['Departamento'] ?? null,
                'provincia' => $cliente['Provincia'] ?? null,
                'distrito' => $cliente['Distrito'] ?? null,
                'urbanizacion_sector' => $cliente['UrbanizacionSector'] ?? null,
                'tipo_via' => null,
                'nombre_via' => $cliente['NombreVia'] ?? $cliente['Domicilio'] ?? null,
                'numero_via' => $cliente['NumeroDomicilio'] ?? null,
                'manzana_lote' => null,
                'telefono_1' => $cliente['Telefono1'] ?? null,
                'telefono_2' => $cliente['Telefono2'] ?? null,
                'partida_electronica' => null,
                'sede_registral' => null,
                'registro' => null,
                'representante' => [],
            ],
        ];

        $datos = array_replace_recursive($base, $registro->DatosRegulatorios ?? []);

        // En JALUD, la operación que origina el ROP es el desembolso de un préstamo.
        // El formato oficial lo registra en "w. Otro (especifique)". Al guardar una
        // CheckboxList de Filament, la selección puede reemplazar el índice 0 del valor
        // inferido y hacer desaparecer OTRO; el snapshot del origen debe conservarlo.
        $tipoOperacionOrigen = strtoupper(trim((string) ($operacion['TipoOperacion'] ?? '')));
        $detalleOperacionOrigen = $operacion['TipoOperacionDetalle'] ?? null;
        $esOtroPorOrigen = $tipoOperacionOrigen === 'OTRO'
            || (blank($tipoOperacionOrigen) && filled($detalleOperacionOrigen));

        $tiposOperacion = is_array($datos['tipos_operacion'] ?? null)
            ? $datos['tipos_operacion']
            : [];

        if ($esOtroPorOrigen && ! in_array('OTRO', $tiposOperacion, true)) {
            $tiposOperacion[] = 'OTRO';
        }

        $datos['tipos_operacion'] = array_values(array_unique($tiposOperacion));

        if (blank($datos['tipo_operacion_otro'] ?? null) && filled($detalleOperacionOrigen)) {
            $datos['tipo_operacion_otro'] = $detalleOperacionOrigen;
        }

        return $datos;
    }

    private function codigoCatalogo(string $tipo, mixed $valor): mixed
    {
        if (! filled($valor)) {
            return $valor;
        }
        try {
            return RopCatalogoItem::query()->where('Tipo', $tipo)
                ->where(fn ($query) => $query->where('Codigo', $valor)->orWhere('Nombre', $valor))
                ->value('Codigo') ?: $valor;
        } catch (\Throwable) {
            return $valor;
        }
    }
}
