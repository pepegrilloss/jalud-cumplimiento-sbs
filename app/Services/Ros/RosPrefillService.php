<?php

namespace App\Services\Ros;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\DeclaracionCliente;
use App\Models\DeclaracionTrabajador;
use App\Models\Pago;
use App\Models\RosConfiguracion;
use App\Models\Trabajador;
use Illuminate\Validation\ValidationException;

class RosPrefillService
{
    public function paraCliente(int $clienteId): array
    {
        return $this->persona(Cliente::query()->findOrFail($clienteId));
    }

    public function personasParaCliente(int $clienteId): array
    {
        $cliente = Cliente::query()->with('garante')->findOrFail($clienteId);
        $personas = [$this->persona($cliente)];

        if ($cliente->garante) {
            $personas[] = $this->persona($cliente->garante, 'GARANTE', 'GARANTE');
        }

        return $personas;
    }

    public function personasParaTrabajador(int $trabajadorId): array
    {
        $trabajador = Trabajador::query()->where('Activo', true)->findOrFail($trabajadorId);
        $declaracion = DeclaracionTrabajador::query()
            ->where('TrabajadorID', $trabajador->TrabajadorID)
            ->latest('NumeroVersion')
            ->first();

        $nombres = $declaracion?->Nombres ?: $trabajador->Nombres;
        $apellidoPaterno = $declaracion?->ApellidoPaterno ?: $trabajador->ApellidoPaterno;
        $apellidoMaterno = $declaracion?->ApellidoMaterno ?: $trabajador->ApellidoMaterno;
        $tipoDocumento = $declaracion?->TipoDocumento ?: $trabajador->TipoDocumento;
        $numeroDocumento = $declaracion?->NumeroDocumento ?: $trabajador->NumeroDocumento;

        $persona = [
            'TipoPersona' => 'NATURAL',
            'ApellidoPaterno' => $apellidoPaterno,
            'ApellidoMaterno' => $apellidoMaterno,
            'Nombres' => $nombres,
            'TipoDocumento' => $tipoDocumento,
            'NumeroDocumento' => $numeroDocumento,
            'FechaNacimiento' => $declaracion?->FechaNacimiento?->format('Y-m-d'),
            'Nacionalidad' => $declaracion?->Nacionalidad,
            'ProfesionOcupacion' => $declaracion?->ProfesionOficio ?: $declaracion?->Cargo,
            'PaisDomicilio' => $declaracion?->Pais,
            'DepartamentoDomicilio' => $declaracion?->DomicilioDepartamento,
            'ProvinciaDomicilio' => $declaracion?->DomicilioProvincia,
            'DistritoDomicilio' => $declaracion?->DomicilioDistrito,
            'UrbanizacionSector' => $declaracion?->DomicilioZona,
            'NombreVia' => $declaracion?->DomicilioVia,
            'NumeroDomicilio' => $declaracion?->DomicilioNumero,
            'Telefono' => $declaracion?->Telefono,
            'EstadoCivil' => $declaracion?->EstadoCivil === 'CONVIVIENTE' ? 'CONCUBINO' : $declaracion?->EstadoCivil,
            'ConyugeNombres' => $declaracion?->ConyugeNombres,
            'CondicionActividad' => 'DEPENDIENTE',
            'IngresoMensual' => $declaracion?->RemuneracionBrutaMensual,
            'MonedaIngreso' => $declaracion ? 'PEN' : null,
            'RelacionEmpresa' => 'EMPLEADO',
            'VinculadoActualmente' => true,
        ];

        return [array_filter($persona, static fn ($valor) => $valor !== null && $valor !== '')];
    }

    public function paraOperacion(int $creditoId, ?int $pagoId = null): array
    {
        $credito = Credito::query()
            ->with(['proposicion.cliente.garante', 'proposicion.tipoCredito'])
            ->findOrFail($creditoId);
        $proposicion = $credito->proposicion;

        if (! $proposicion?->cliente) {
            throw ValidationException::withMessages([
                'CreditoID' => 'El crédito no tiene cliente y propuesta completos en JALUD.',
            ]);
        }

        $pago = $pagoId
            ? Pago::query()->where('CreditoID', $creditoId)->where('Activo', true)->findOrFail($pagoId)
            : null;
        $fecha = $pago?->FechaPago
            ?? $credito->FechaGeneracion
            ?? $proposicion->FechaDesembolso
            ?? $proposicion->FechaPropuesta;
        $monto = $pago ? (float) $pago->MontoPagado : (float) $proposicion->MontoTotal;
        $codigoCredito = $proposicion->CodigoCredito ?: "Crédito #{$credito->CreditoID}";

        return [
            'caso' => [
                'SedeID' => $credito->SedeID,
                'ZonaID' => $proposicion->ZonaID,
                'ClienteID' => $proposicion->ClienteID,
                'CreditoID' => $credito->CreditoID,
                'PagoID' => $pago?->PagoID,
                'MontoTotal' => $monto,
                'Moneda' => 'PEN',
                'FechaOperacionDesde' => $fecha?->format('Y-m-d'),
                'FechaOperacionHasta' => $fecha?->format('Y-m-d'),
            ],
            'personas' => $this->personasParaCliente((int) $proposicion->ClienteID),
            'operaciones' => [[
                'ClienteID' => $proposicion->ClienteID,
                'CreditoID' => $credito->CreditoID,
                'PagoID' => $pago?->PagoID,
                'ProductoServicio' => 'Préstamo',
                'CodigoProducto' => 'ff',
                'NumeroOperacion' => $pago ? "Pago #{$pago->PagoID} · {$codigoCredito}" : $codigoCredito,
                'Monto' => $monto,
                'Moneda' => 'PEN',
                'FechaOperacion' => $fecha?->format('Y-m-d'),
                'Orden' => 1,
                'Detalle' => $pago
                    ? "Pago relacionado al préstamo {$codigoCredito}"
                    : ($proposicion->tipoCredito?->Descripcion ?? $proposicion->tipoCredito?->Nombre ?? 'Préstamo'),
            ]],
        ];
    }

    private function persona(Cliente $cliente, string $condicion = 'TITULAR', string $relacion = 'CLIENTE'): array
    {
        $declaracion = DeclaracionCliente::query()
            ->where('ClienteID', $cliente->ClienteID)
            ->where('Estado', 'FINALIZADO')
            ->latest('NumeroVersion')
            ->first();

        $persona = [
            'ClienteID' => $cliente->ClienteID,
            'TipoPersona' => 'NATURAL',
            'CondicionParticipacion' => $condicion,
            'ApellidoPaterno' => $cliente->ApellidoPaterno,
            'ApellidoMaterno' => $cliente->ApellidoMaterno,
            'Nombres' => $cliente->Nombres,
            'TipoDocumento' => $declaracion?->TipoDocumento ?? 'DNI',
            'NumeroDocumento' => $declaracion?->NumeroDocumento ?? $cliente->DNI,
            'FechaNacimiento' => $cliente->FechaNacimiento?->format('Y-m-d'),
            'Nacionalidad' => $declaracion?->Nacionalidad,
            'EsPep' => $declaracion?->EstadoPep ? in_array($declaracion->EstadoPep, ['SI_SOY', 'SI_HE_SIDO'], true) : null,
            'ProfesionOcupacion' => $declaracion?->Ocupacion,
            'Domicilio' => $declaracion?->DomicilioVia ?? $cliente->Domicilio,
            'UrbanizacionSector' => $declaracion?->DomicilioZona,
            'NombreVia' => $declaracion?->DomicilioVia,
            'NumeroDomicilio' => $declaracion?->DomicilioNumero,
            'DistritoDomicilio' => $declaracion?->DomicilioDistrito,
            'ProvinciaDomicilio' => $declaracion?->DomicilioProvincia,
            'DepartamentoDomicilio' => $declaracion?->DomicilioDepartamento,
            'Telefono' => $declaracion?->Celular ?? $declaracion?->TelefonoFijo,
            'Telefono2' => $declaracion?->TelefonoFijo,
            'Correo' => $declaracion?->Correo,
            'EstadoCivil' => $declaracion?->EstadoCivil,
            'RelacionEmpresa' => $relacion,
        ];

        return array_filter($persona, static fn ($valor) => $valor !== null && $valor !== '');
    }

    public function configuracion(int $sedeId): array
    {
        return RosConfiguracion::query()
            ->where('SedeID', $sedeId)
            ->first()?->only([
                'EnteSupervisor', 'TipoSujetoObligado', 'CodigoEmpresa',
                'CodigoOficialCumplimiento', 'OficinaInformante', 'Departamento',
                'Provincia', 'Distrito',
            ]) ?? [];
    }
}
