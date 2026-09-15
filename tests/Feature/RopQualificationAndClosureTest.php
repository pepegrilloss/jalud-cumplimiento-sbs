<?php

namespace Tests\Feature;

use App\Models\RopAuditoria;
use App\Models\RopPeriodo;
use App\Models\RopRegistro;
use App\Services\Rop\RopPeriodService;
use App\Services\Rop\RopQualificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Unit\RopCompletenessServiceTest;

class RopQualificationAndClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_qualification_and_monthly_closure_are_audited_and_closed_periods_are_immutable(): void
    {
        $periodo = RopPeriodo::create(['SedeID' => 1, 'Periodo' => '2026-09-01', 'Estado' => RopPeriodo::ESTADO_ABIERTO]);
        $rop = RopRegistro::create([
            'NumeroRop' => 'ROP-000001', 'RopPeriodoID' => $periodo->getKey(), 'SedeID' => 1,
            'ClienteID' => 1, 'ProposicionCreditoID' => 1, 'CreditoID' => 1, 'CodigoCredito' => 'C-1',
            'ClienteNombre' => 'CLIENTE PRUEBA', 'NumeroDocumento' => '12345678', 'Periodo' => '2026-09-01',
            'FechaOperacion' => '2026-09-10 10:00:00', 'Monto' => 5000, 'UmbralAplicado' => 2000,
            'Moneda' => 'PEN', 'Estado' => RopRegistro::ESTADO_PENDIENTE, 'SnapshotDatos' => [],
            'DatosRegulatorios' => RopCompletenessServiceTest::datosCompletos(),
        ]);

        $rop = app(RopQualificationService::class)->calificar($rop, RopRegistro::CALIFICACION_SOSPECHOSA, 'Operación sin sustento suficiente');
        self::assertSame(RopRegistro::ESTADO_CALIFICADO, $rop->Estado);
        self::assertDatabaseHas('rop_auditorias', ['RopRegistroID' => $rop->getKey(), 'Accion' => 'CALIFICAR']);

        $periodo = app(RopPeriodService::class)->cerrar(1, '2026-09-01');
        self::assertSame(RopPeriodo::ESTADO_CERRADO, $periodo->Estado);
        self::assertTrue(RopAuditoria::where('RopRegistroID', $rop->getKey())->where('Accion', 'CERRAR_PERIODO')->exists());

        $this->expectException(ValidationException::class);
        app(RopQualificationService::class)->calificar($rop, null, 'Nueva revisión');
    }

    public function test_review_without_anomalies_is_qualified_with_null_classification(): void
    {
        $periodo = RopPeriodo::create(['SedeID' => 1, 'Periodo' => '2026-09-01', 'Estado' => RopPeriodo::ESTADO_ABIERTO]);
        $rop = RopRegistro::create([
            'NumeroRop' => 'ROP-000002', 'RopPeriodoID' => $periodo->getKey(), 'SedeID' => 1,
            'ClienteID' => 1, 'ProposicionCreditoID' => 1, 'CreditoID' => 2, 'CodigoCredito' => 'C-2',
            'ClienteNombre' => 'CLIENTE SIN ANOMALIAS', 'NumeroDocumento' => '12345678', 'Periodo' => '2026-09-01',
            'FechaOperacion' => '2026-09-10 10:00:00', 'Monto' => 5000, 'UmbralAplicado' => 2000,
            'Moneda' => 'PEN', 'Estado' => RopRegistro::ESTADO_PENDIENTE, 'SnapshotDatos' => [],
            'DatosRegulatorios' => RopCompletenessServiceTest::datosCompletos(),
        ]);

        $rop = app(RopQualificationService::class)->calificar($rop, null, 'Revisado sin anomalías');

        self::assertSame(RopRegistro::ESTADO_CALIFICADO, $rop->Estado);
        self::assertNull($rop->Calificacion);
        self::assertFalse($rop->puedeGenerarRos());
    }

    public function test_incomplete_rop_cannot_be_qualified(): void
    {
        $periodo = RopPeriodo::create(['SedeID' => 1, 'Periodo' => '2026-09-01', 'Estado' => RopPeriodo::ESTADO_ABIERTO]);
        $rop = RopRegistro::create([
            'NumeroRop' => 'ROP-000003', 'RopPeriodoID' => $periodo->getKey(), 'SedeID' => 1,
            'ClienteID' => 1, 'ProposicionCreditoID' => 1, 'CreditoID' => 3, 'CodigoCredito' => 'C-3',
            'ClienteNombre' => 'CLIENTE INCOMPLETO', 'NumeroDocumento' => '12345678', 'Periodo' => '2026-09-01',
            'FechaOperacion' => '2026-09-10 10:00:00', 'Monto' => 5000, 'UmbralAplicado' => 2000,
            'Moneda' => 'PEN', 'Estado' => RopRegistro::ESTADO_PENDIENTE, 'SnapshotDatos' => [],
        ]);

        $this->expectException(ValidationException::class);
        app(RopQualificationService::class)->calificar($rop, RopRegistro::CALIFICACION_INUSUAL, 'Revisión');
    }
}
