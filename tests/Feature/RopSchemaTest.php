<?php

namespace Tests\Feature;

use App\Models\RopPeriodo;
use App\Models\RopRegistro;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RopSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rop_schema_contains_detection_snapshot_qualification_closure_and_ros_linkage(): void
    {
        self::assertTrue(Schema::hasColumns('rop_registros', [
            'NumeroRop', 'CreditoID', 'Monto', 'UmbralAplicado', 'Estado', 'Calificacion',
            'MotivoCalificacion', 'ObservacionesCalificacion', 'CalificadoPorID', 'CalificadoEn',
            'SnapshotDatos', 'DatosRegulatorios', 'RosCasoID',
        ]));
        self::assertTrue(Schema::hasColumns('rop_catalogo_items', [
            'Tipo', 'Codigo', 'Nombre', 'Grupo', 'Activo', 'Orden',
        ]));
        self::assertTrue(Schema::hasColumns('rop_periodos', ['SedeID', 'Periodo', 'Estado', 'CerradoPorID', 'CerradoEn']));
        self::assertTrue(Schema::hasColumns('rop_auditorias', ['RopRegistroID', 'RopPeriodoID', 'Accion', 'ValoresAnteriores', 'ValoresNuevos']));
        self::assertTrue(Schema::hasColumn('ros_configuraciones', 'UmbralRop'));
        self::assertTrue(Schema::hasColumn('ros_casos', 'RopRegistroID'));
    }

    public function test_ros_origin_is_explicit_and_rop_deduplication_uses_the_source_operation(): void
    {
        self::assertTrue(Schema::hasColumns('rop_registros', ['OrigenTipo', 'OrigenID']));
        self::assertTrue(Schema::hasColumn('ros_casos', 'OrigenRos'));

        $ropIndexes = collect(Schema::getIndexes('rop_registros'));
        self::assertTrue($ropIndexes->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['OrigenTipo', 'OrigenID']
        ));
        self::assertFalse($ropIndexes->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['CreditoID']
        ));

        $ropColumn = collect(Schema::getColumns('ros_casos'))->firstWhere('name', 'RopRegistroID');
        self::assertTrue($ropColumn['nullable']);
    }

    public function test_one_credit_accepts_different_source_operations_but_not_the_same_operation_twice(): void
    {
        $periodo = RopPeriodo::create([
            'SedeID' => 1,
            'Periodo' => '2026-09-01',
            'Estado' => RopPeriodo::ESTADO_ABIERTO,
        ]);
        $base = [
            'RopPeriodoID' => $periodo->getKey(),
            'SedeID' => 1,
            'ClienteID' => 1,
            'ProposicionCreditoID' => 1,
            'CreditoID' => 77,
            'CodigoCredito' => 'C-77',
            'ClienteNombre' => 'CLIENTE PRUEBA',
            'Periodo' => '2026-09-01',
            'FechaOperacion' => '2026-09-11 10:00:00',
            'Monto' => 2500,
            'UmbralAplicado' => 2000,
            'SnapshotDatos' => [],
        ];

        RopRegistro::create($base + ['NumeroRop' => 'ROP-1', 'OrigenTipo' => 'PAGO', 'OrigenID' => 10]);
        RopRegistro::create($base + ['NumeroRop' => 'ROP-2', 'OrigenTipo' => 'PAGO', 'OrigenID' => 11]);
        self::assertSame(2, RopRegistro::withoutGlobalScopes()->where('CreditoID', 77)->count());

        $this->expectException(QueryException::class);
        RopRegistro::create($base + ['NumeroRop' => 'ROP-3', 'OrigenTipo' => 'PAGO', 'OrigenID' => 10]);
    }
}
