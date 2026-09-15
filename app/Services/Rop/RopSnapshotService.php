<?php

namespace App\Services\Rop;

use App\Models\Credito;
use App\Models\DeclaracionCliente;
use App\Models\RosConfiguracion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RopSnapshotService
{
    public function crear(Credito $credito, RosConfiguracion $configuracion): array
    {
        $credito->loadMissing([
            'proposicion.cliente.garante',
            'proposicion.cliente.negocio.ciudad',
            'proposicion.zona.ciudad',
            'proposicion.tipoCredito',
            'tipoPago',
            'pagos' => fn ($query) => $query->where('Activo', true)->orderBy('FechaPago')->orderBy('PagoID'),
            'sede',
        ]);

        $proposicion = $credito->proposicion;
        $cliente = $proposicion?->cliente;

        if (! $proposicion || ! $cliente) {
            throw ValidationException::withMessages(['CreditoID' => 'El crédito no tiene cliente y propuesta completos en JALUD.']);
        }

        $declaracion = DeclaracionCliente::withoutGlobalScopes()
            ->where('ClienteID', $cliente->ClienteID)
            ->where('Estado', 'FINALIZADO')
            ->latest('NumeroVersion')
            ->first();

        $negocio = $cliente->negocio;
        $giro = $negocio?->GiroID
            ? DB::connection('jalud')->table('Giro')->where('GiroID', $negocio->GiroID)->value('Descripcion')
            : null;
        $subGiro = $negocio?->SubGiroID
            ? DB::connection('jalud')->table('SubGiro')->where('SubGiroID', $negocio->SubGiroID)->value('Descripcion')
            : null;

        return [
            'configuracion' => [
                'EnteSupervisor' => $configuracion->EnteSupervisor,
                'TipoSujetoObligado' => $configuracion->TipoSujetoObligado,
                'CodigoEmpresa' => $configuracion->CodigoEmpresa,
                'CodigoOficialCumplimiento' => $configuracion->CodigoOficialCumplimiento,
                'OficinaInformante' => $configuracion->OficinaInformante,
                'Departamento' => $configuracion->Departamento,
                'Provincia' => $configuracion->Provincia,
                'Distrito' => $configuracion->Distrito,
            ],
            'cliente' => [
                'ClienteID' => $cliente->ClienteID,
                'TipoPersona' => 'NATURAL',
                'ApellidoPaterno' => $cliente->ApellidoPaterno,
                'ApellidoMaterno' => $cliente->ApellidoMaterno,
                'Nombres' => $cliente->Nombres,
                'NombresApellidos' => $cliente->NombresApellidos,
                'TipoDocumento' => $declaracion?->TipoDocumento ?? 'DNI',
                'NumeroDocumento' => $declaracion?->NumeroDocumento ?? $cliente->DNI,
                'FechaNacimiento' => $cliente->FechaNacimiento?->format('Y-m-d'),
                'Nacionalidad' => $declaracion?->Nacionalidad,
                'ProfesionOcupacion' => $declaracion?->Ocupacion,
                'ActividadEconomica' => $subGiro ?: $giro,
                'SectorActividad' => $giro,
                'Domicilio' => $cliente->Domicilio,
                'Departamento' => $declaracion?->DomicilioDepartamento,
                'Provincia' => $declaracion?->DomicilioProvincia,
                'Distrito' => $declaracion?->DomicilioDistrito,
                'UrbanizacionSector' => $declaracion?->DomicilioZona,
                'NombreVia' => $declaracion?->DomicilioVia,
                'NumeroDomicilio' => $declaracion?->DomicilioNumero,
                'Telefono1' => $declaracion?->Celular ?? $declaracion?->TelefonoFijo,
                'Telefono2' => $declaracion?->TelefonoFijo,
                'Correo' => $declaracion?->Correo,
                'EstadoCivil' => $declaracion?->EstadoCivil,
                'Conyuge' => $declaracion?->ConyugeConviviente ?? $cliente->ConyugeNombresApellidos,
                'DireccionNegocio' => $negocio?->DireccionNegocio,
                'CiudadNegocio' => $negocio?->ciudad?->Nombre,
                'ZonaNegocio' => $negocio?->zona?->Nombre,
            ],
            'credito' => [
                'CreditoID' => $credito->CreditoID,
                'ProposicionCreditoID' => $proposicion->ProposicionCreditoID,
                'CodigoCredito' => $proposicion->CodigoCredito,
                'TipoCredito' => $proposicion->tipoCredito?->Descripcion,
                'MontoTotal' => (float) $proposicion->MontoTotal,
                'MontoTotalPagar' => (float) $proposicion->MontoTotalPagar,
                'SaldoPendiente' => (float) $proposicion->SaldoPendiente,
                'TasaInteres' => (float) $proposicion->TasaInteres,
                'TasaMora' => (float) $proposicion->TasaMora,
                'Plazo' => $proposicion->Plazo,
                'NumeroCuotas' => $proposicion->NumeroCuotas,
                'MontoCuota' => (float) $proposicion->MontoCuota,
                'FechaPropuesta' => $proposicion->FechaPropuesta?->toIso8601String(),
                'FechaAprobacion' => $proposicion->FechaAprobacion?->toIso8601String(),
                'FechaDesembolso' => $proposicion->FechaDesembolso?->toIso8601String(),
                'FechaGeneracion' => $credito->FechaGeneracion?->toIso8601String(),
                'FechaInicio' => $credito->FechaInicio?->format('Y-m-d'),
                'FechaVencimiento' => $credito->FechaVencimiento?->format('Y-m-d'),
                'TipoPago' => $credito->tipoPago?->Nombre,
                'Estado' => $proposicion->Estado,
            ],
            'operacion' => [
                'TipoOperacion' => 'OTRO',
                'TipoOperacionDetalle' => 'PRÉSTAMO',
                'IndividualMultiple' => 'INDIVIDUAL',
                'FormaPagoDesembolso' => [(int) $proposicion->NumeroCuotas > 1 ? 'A_PLAZOS' : 'AL_CONTADO'],
                'OportunidadPago' => null,
                'InformacionAdicional' => $proposicion->Observaciones,
                'Moneda' => 'PEN',
                'TipoCambio' => null,
            ],
            'pagos' => $credito->pagos->map(fn ($pago): array => [
                'PagoID' => $pago->PagoID,
                'FechaPago' => $pago->FechaPago?->toIso8601String(),
                'MontoPagado' => (float) $pago->MontoPagado,
                'TipoPago' => $pago->TipoPago,
                'TipoConcepto' => $pago->TipoConcepto,
                'EsMora' => (bool) $pago->EsMora,
                'EsPagoAMayor' => (bool) $pago->EsPagoAMayor,
                'Comentario' => $pago->Comentario,
            ])->all(),
            'garantia' => $cliente->garante ? [
                'Tipo' => 'GARANTE',
                'ClienteID' => $cliente->garante->ClienteID,
                'Documento' => $cliente->garante->DNI,
                'Nombre' => $cliente->garante->NombresApellidos,
                'Domicilio' => $cliente->garante->Domicilio,
            ] : null,
            'sede' => [
                'SedeID' => $credito->SedeID,
                'Nombre' => $credito->sede?->Nombre,
                'Codigo' => $credito->sede?->Codigo,
                'ZonaID' => $proposicion->ZonaID,
                'Zona' => $proposicion->zona?->Nombre,
                'Ciudad' => $proposicion->zona?->ciudad?->Nombre,
            ],
        ];
    }
}
