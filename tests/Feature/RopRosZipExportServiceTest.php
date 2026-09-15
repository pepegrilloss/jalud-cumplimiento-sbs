<?php

namespace Tests\Feature;

use App\Models\RopPeriodo;
use App\Models\RopRegistro;
use App\Models\RosCaso;
use App\Models\User;
use App\Services\Cumplimiento\RopRosZipExportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Unit\RopCompletenessServiceTest;
use ZipArchive;

class RopRosZipExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.jalud', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('jalud');
        Schema::connection('jalud')->create('Sede', function (Blueprint $table): void {
            $table->increments('SedeID');
            $table->string('Nombre');
            $table->string('Codigo')->nullable();
            $table->boolean('Activo')->default(true);
        });
        DB::connection('jalud')->table('Sede')->insert([
            'SedeID' => 1, 'Nombre' => 'Chiclayo 01', 'Codigo' => 'CH01', 'Activo' => true,
        ]);
    }

    public function test_zip_contains_complete_rop_and_ros_in_range_without_exporting_incomplete_rop(): void
    {
        $usuario = User::create([
            'name' => 'Oficial', 'username' => 'OFICIAL_REPORTES', 'password' => 'secret', 'SedeID' => 1,
        ]);
        $this->actingAs($usuario);
        session(['sede_activa' => 1]);

        $periodo = RopPeriodo::create([
            'SedeID' => 1, 'Periodo' => '2026-07-01', 'Estado' => RopPeriodo::ESTADO_ABIERTO,
        ]);
        $baseRop = [
            'RopPeriodoID' => $periodo->getKey(), 'SedeID' => 1, 'ClienteID' => 1,
            'ProposicionCreditoID' => 1, 'CreditoID' => 1, 'CodigoCredito' => 'C-1',
            'ClienteNombre' => 'CLIENTE PRUEBA', 'NumeroDocumento' => '12345678', 'Periodo' => '2026-07-01',
            'Monto' => 5000, 'UmbralAplicado' => 2000, 'Moneda' => 'PEN',
            'Estado' => RopRegistro::ESTADO_PENDIENTE, 'SnapshotDatos' => [],
        ];
        RopRegistro::create(array_merge($baseRop, [
            'NumeroRop' => 'ROP-000001', 'OrigenTipo' => 'CREDITO_DESEMBOLSO', 'OrigenID' => 1,
            'FechaOperacion' => '2026-07-11 10:00:00',
            'DatosRegulatorios' => RopCompletenessServiceTest::datosCompletos(),
        ]));
        RopRegistro::create(array_merge($baseRop, [
            'NumeroRop' => 'ROP-000002', 'OrigenTipo' => 'CREDITO_DESEMBOLSO', 'OrigenID' => 2,
            'CreditoID' => 2, 'FechaOperacion' => '2026-07-12 10:00:00', 'DatosRegulatorios' => [],
        ]));
        RopRegistro::create(array_merge($baseRop, [
            'NumeroRop' => 'ROP-000003', 'OrigenTipo' => 'CREDITO_DESEMBOLSO', 'OrigenID' => 3,
            'CreditoID' => 3, 'FechaOperacion' => '2026-08-01 10:00:00',
            'DatosRegulatorios' => RopCompletenessServiceTest::datosCompletos(),
        ]));

        RosCaso::create([
            'SedeID' => 1, 'CodigoInterno' => 'ROS-EN-RANGO', 'Estado' => RosCaso::ESTADO_BORRADOR,
            'FechaOperacionDesde' => '2026-07-13', 'FechaOperacionHasta' => '2026-07-13',
        ]);
        RosCaso::create([
            'SedeID' => 1, 'CodigoInterno' => 'ROS-FUERA-RANGO', 'Estado' => RosCaso::ESTADO_BORRADOR,
            'FechaOperacionDesde' => '2026-08-02', 'FechaOperacionHasta' => '2026-08-02',
        ]);

        $ruta = app(RopRosZipExportService::class)->generar(
            now()->setDate(2026, 7, 1),
            now()->setDate(2026, 7, 31),
        );

        try {
            $zip = new ZipArchive;
            self::assertTrue($zip->open($ruta) === true);
            self::assertNotFalse($zip->locateName('ROP/ROP-000001.xlsx'));
            self::assertFalse($zip->locateName('ROP/ROP-000002.xlsx'));
            self::assertFalse($zip->locateName('ROP/ROP-000003.xlsx'));
            self::assertNotFalse($zip->locateName('ROS/ROS-EN-RANGO.xls'));
            self::assertFalse($zip->locateName('ROS/ROS-FUERA-RANGO.xls'));
            self::assertStringContainsString('ROP incompletos omitidos: 1', $zip->getFromName('RESUMEN.txt'));
            $zip->close();
        } finally {
            @unlink($ruta);
        }
    }
}
