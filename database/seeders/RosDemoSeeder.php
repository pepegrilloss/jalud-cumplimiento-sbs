<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\RosCaso;
use App\Models\RosCatalogoItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::query()->where('username', 'ADMIN_SBS')->firstOrFail();
        Auth::login($usuario);

        $cliente = Cliente::withoutGlobalScope('sede')->findOrFail(223);
        $pago = DB::connection('jalud')->table('pago')
            ->where('CreditoID', 416)->where('Activo', true)->where('PagoID', 138281)
            ->first(['PagoID', 'MontoPagado', 'FechaPago']);

        DB::transaction(function () use ($usuario, $cliente, $pago): void {
            $caso = RosCaso::withoutGlobalScope('sede')->firstOrNew(['CodigoInterno' => 'ROS-DEMO-2026-001']);
            $caso->fill([
                'SedeID' => 1, 'ZonaID' => 1, 'ClienteID' => 223, 'CreditoID' => 416, 'PagoID' => 138281,
                'Estado' => RosCaso::ESTADO_BORRADOR, 'ClaseReporte' => 'INICIAL', 'NumeroReporte' => 'DEMO-001',
                'FechaReporte' => now()->toDateString(), 'EnteSupervisor' => 'SBS/UIF-PERU',
                'TipoSujetoObligado' => RosCatalogoItem::query()->where('Tipo', 'TIPO_SUJETO')->where('Nombre', 'like', '%prestamo%')->value('Codigo'),
                'CodigoEmpresa' => 'SBS-DEMO-001', 'CodigoOficialCumplimiento' => 'OC-DEMO-001',
                'OficinaInformante' => 'Agencia Chiclayo', 'OficinaOperacion' => 'Agencia Chiclayo',
                'DepartamentoOperacion' => 'Lambayeque', 'ProvinciaOperacion' => 'Chiclayo', 'DistritoOperacion' => 'Chiclayo',
                'FechaOperacionDesde' => $pago?->FechaPago ?: now()->toDateString(), 'FechaOperacionHasta' => $pago?->FechaPago ?: now()->toDateString(),
                'MontoTotal' => $pago?->MontoPagado ?: 5000, 'Moneda' => 'PEN', 'Alcance' => 'NACIONAL',
                'DelitoPrecedente' => RosCatalogoItem::query()->where('Tipo', 'DELITO_PRECEDENTE')->value('Codigo'),
                'SectorEconomico' => RosCatalogoItem::query()->where('Tipo', 'SECTOR')->value('Codigo'),
                'ActividadEconomica' => RosCatalogoItem::query()->where('Tipo', 'ACTIVIDAD')->value('Codigo'),
                'DescripcionHechos' => 'CASO FICTICIO: se detectó una operación que requiere evaluación por no guardar relación aparente con el perfil declarado del cliente.',
                'RelacionPersonas' => 'El cliente titular realizó directamente la operación vinculada al crédito seleccionado.',
                'RelacionProductosServicios' => 'La operación está relacionada con un crédito de consumo y sus pagos.',
                'CaracteristicasSospechosas' => 'Monto y comportamiento ficticios utilizados exclusivamente para validar el formato ROS.',
                'ExplicacionesJustificaciones' => 'El cliente indicó, para esta demostración, que los fondos proceden de actividad comercial.',
                'RelacionOtrasOperaciones' => 'No se identificaron otras operaciones relacionadas en este caso de prueba.',
                'InformacionExcluida' => 'Ninguna.', 'FuentesConsultadas' => 'Jalud, RENIEC y documentación interna (datos ficticios).',
                'DocumentacionComplementaria' => 'Ficha del cliente, crédito C-000416 y comprobante de pago 138281.',
            ]);
            $caso->CreadoPorID = $caso->exists ? $caso->CreadoPorID : $usuario->id;
            $caso->ActualizadoPorID = $usuario->id;
            $caso->save();

            $caso->personas()->delete();
            $persona = $caso->personas()->create([
                'SedeID' => 1, 'ClienteID' => 223, 'TipoPersona' => 'NATURAL', 'CondicionParticipacion' => 'CLIENTE',
                'ApellidoPaterno' => $cliente->ApellidoPaterno ?: 'PÉREZ', 'ApellidoMaterno' => $cliente->ApellidoMaterno ?: 'DEMO',
                'Nombres' => $cliente->Nombres ?: $cliente->NombresApellidos, 'TipoDocumento' => 'DNI', 'NumeroDocumento' => $cliente->DNI,
                'FechaNacimiento' => $cliente->FechaNacimiento, 'Nacionalidad' => 'PERUANA', 'EsPep' => false,
                'ProfesionOcupacion' => 'COMERCIANTE', 'Domicilio' => $cliente->Domicilio ?: 'DIRECCIÓN FICTICIA',
                'PaisDomicilio' => 'PERÚ', 'DepartamentoDomicilio' => 'LAMBAYEQUE', 'ProvinciaDomicilio' => 'CHICLAYO',
                'DistritoDomicilio' => 'CHICLAYO', 'TipoVia' => RosCatalogoItem::query()->where('Tipo', 'VIA')->value('Codigo'),
                'NombreVia' => 'AVENIDA DEMOSTRACIÓN', 'NumeroDomicilio' => '123', 'Telefono' => '999999999',
                'Correo' => 'demo@example.com', 'EstadoCivil' => 'SOLTERO', 'CondicionActividad' => 'INDEPENDIENTE',
                'IngresoMensual' => 3500, 'MonedaIngreso' => 'PEN', 'RelacionEmpresa' => 'CLIENTE', 'VinculadoActualmente' => true,
            ]);

            $caso->operaciones()->delete();
            $caso->operaciones()->create([
                'SedeID' => 1, 'ClienteID' => 223, 'CreditoID' => 416, 'PagoID' => 138281,
                'CodigoProducto' => 'h', 'ProductoServicio' => 'Crédito de consumo', 'NumeroOperacion' => 'C-000416',
                'Monto' => $pago?->MontoPagado ?: 5000, 'Moneda' => 'PEN', 'FechaOperacion' => $pago?->FechaPago ?: now(),
                'Orden' => 1, 'Detalle' => 'Operación ficticia para validar la exportación ROS.',
            ]);

            $caso->senalesAlerta()->delete();
            $senal = RosCatalogoItem::query()->where('Tipo', 'SENAL_OPERACION')->where('Activo', true)->first();
            if ($senal) $caso->senalesAlerta()->create(['SedeID' => 1, 'Tipo' => 'OPERACION', 'Codigo' => $senal->Codigo, 'Descripcion' => $senal->Nombre]);

            $caso->tipologias()->delete();
            $tipologia = RosCatalogoItem::query()->where('Tipo', 'TIPOLOGIA')->where('Activo', true)->first();
            if ($tipologia) $caso->tipologias()->create(['SedeID' => 1, 'Codigo' => $tipologia->Codigo, 'Descripcion' => $tipologia->Nombre]);
        });
    }
}
