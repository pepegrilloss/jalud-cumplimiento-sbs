<?php

namespace App\Services\Rop;

use App\Models\RopRegistro;
use App\Models\RosCaso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RopToRosService
{
    public function __construct(private readonly RopAuditService $audit) {}

    public function generar(RopRegistro $registro): RosCaso
    {
        return DB::transaction(function () use ($registro): RosCaso {
            $registro = RopRegistro::withoutGlobalScopes()->lockForUpdate()->findOrFail($registro->getKey());

            if ($registro->RosCasoID) {
                return RosCaso::withoutGlobalScopes()->findOrFail($registro->RosCasoID);
            }

            if (! $registro->puedeGenerarRos()) {
                throw ValidationException::withMessages([
                    'Calificacion' => 'Solo un ROP calificado como Sospechosa puede generar un ROS.',
                ]);
            }

            $snapshot = $registro->SnapshotDatos;
            $config = data_get($snapshot, 'configuracion', []);
            $cliente = data_get($snapshot, 'cliente', []);
            $credito = data_get($snapshot, 'credito', []);
            $sede = data_get($snapshot, 'sede', []);

            $caso = RosCaso::create([
                'RopRegistroID' => $registro->RopRegistroID,
                'OrigenRos' => RosCaso::ORIGEN_ROP,
                'SedeID' => $registro->SedeID,
                'ZonaID' => $registro->ZonaID,
                'ClienteID' => $registro->ClienteID,
                'CreditoID' => $registro->CreditoID,
                'Estado' => RosCaso::ESTADO_BORRADOR,
                'EnteSupervisor' => $config['EnteSupervisor'] ?? 'SBS/UIF-PERU',
                'TipoSujetoObligado' => $config['TipoSujetoObligado'] ?? null,
                'CodigoEmpresa' => $config['CodigoEmpresa'] ?? null,
                'CodigoOficialCumplimiento' => $config['CodigoOficialCumplimiento'] ?? null,
                'OficinaInformante' => $config['OficinaInformante'] ?? null,
                'OficinaOperacion' => $sede['Nombre'] ?? null,
                'DepartamentoOperacion' => $config['Departamento'] ?? null,
                'ProvinciaOperacion' => $config['Provincia'] ?? null,
                'DistritoOperacion' => $config['Distrito'] ?? null,
                'FechaOperacionDesde' => $registro->FechaOperacion,
                'FechaOperacionHasta' => $registro->FechaOperacion,
                'MontoTotal' => $registro->Monto,
                'Moneda' => $registro->Moneda,
                'SectorEconomico' => $cliente['SectorActividad'] ?? null,
                'ActividadEconomica' => $cliente['ActividadEconomica'] ?? null,
                'DescripcionHechos' => "Operación originada en {$registro->NumeroRop}. Crédito {$registro->CodigoCredito}.",
                'CaracteristicasSospechosas' => $registro->MotivoCalificacion,
                'ExplicacionesJustificaciones' => $registro->ObservacionesCalificacion,
            ]);

            $caso->personas()->create(array_filter([
                'SedeID' => $registro->SedeID,
                'ClienteID' => $registro->ClienteID,
                'TipoPersona' => $cliente['TipoPersona'] ?? 'NATURAL',
                'CondicionParticipacion' => 'TITULAR',
                'ApellidoPaterno' => $cliente['ApellidoPaterno'] ?? null,
                'ApellidoMaterno' => $cliente['ApellidoMaterno'] ?? null,
                'Nombres' => $cliente['Nombres'] ?? null,
                'TipoDocumento' => $cliente['TipoDocumento'] ?? 'DNI',
                'NumeroDocumento' => $cliente['NumeroDocumento'] ?? null,
                'FechaNacimiento' => $cliente['FechaNacimiento'] ?? null,
                'Nacionalidad' => $cliente['Nacionalidad'] ?? null,
                'ProfesionOcupacion' => $cliente['ProfesionOcupacion'] ?? null,
                'ActividadEconomica' => $cliente['ActividadEconomica'] ?? null,
                'Domicilio' => $cliente['Domicilio'] ?? null,
                'DepartamentoDomicilio' => $cliente['Departamento'] ?? null,
                'ProvinciaDomicilio' => $cliente['Provincia'] ?? null,
                'DistritoDomicilio' => $cliente['Distrito'] ?? null,
                'UrbanizacionSector' => $cliente['UrbanizacionSector'] ?? null,
                'NombreVia' => $cliente['NombreVia'] ?? null,
                'NumeroDomicilio' => $cliente['NumeroDomicilio'] ?? null,
                'Telefono' => $cliente['Telefono1'] ?? null,
                'Telefono2' => $cliente['Telefono2'] ?? null,
                'Correo' => $cliente['Correo'] ?? null,
                'EstadoCivil' => $cliente['EstadoCivil'] ?? null,
                'ConyugeNombres' => $cliente['Conyuge'] ?? null,
                'RelacionEmpresa' => 'CLIENTE',
            ], static fn ($value) => $value !== null && $value !== ''));

            $caso->operaciones()->create([
                'SedeID' => $registro->SedeID,
                'ClienteID' => $registro->ClienteID,
                'CreditoID' => $registro->CreditoID,
                'ProductoServicio' => 'Préstamo',
                'CodigoProducto' => 'ff',
                'NumeroOperacion' => $registro->CodigoCredito,
                'Monto' => $registro->Monto,
                'Moneda' => $registro->Moneda,
                'FechaOperacion' => $registro->FechaOperacion,
                'Orden' => 1,
                'Detalle' => $credito['TipoCredito'] ?? 'Préstamo',
            ]);

            $registro->RosCasoID = $caso->RosCasoID;
            $registro->saveQuietly();
            $this->audit->registrar('GENERAR_ROS', $registro, nuevos: ['RosCasoID' => $caso->RosCasoID]);

            return $caso->refresh();
        });
    }
}
