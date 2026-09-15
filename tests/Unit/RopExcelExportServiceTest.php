<?php

namespace Tests\Unit;

use App\Models\RopRegistro;
use App\Services\Rop\RopExcelExportService;
use Tests\TestCase;

class RopExcelExportServiceTest extends TestCase
{
    public function test_it_fills_the_official_template_from_the_historical_snapshot(): void
    {
        $rop = new RopRegistro([
            'NumeroRop' => 'ROP-000123',
            'FechaOperacion' => '2026-09-10 08:30:00',
            'Monto' => 5000,
            'Moneda' => 'PEN',
            'SnapshotDatos' => [
                'configuracion' => ['EnteSupervisor' => 'SBS', 'CodigoEmpresa' => 'EMP-1', 'CodigoOficialCumplimiento' => 'OC-1', 'TipoSujetoObligado' => 'Otro'],
                'cliente' => ['ApellidoPaterno' => 'PEREZ', 'ApellidoMaterno' => 'LOPEZ', 'Nombres' => 'JUAN', 'TipoDocumento' => 'DNI', 'NumeroDocumento' => '12345678'],
                'operacion' => ['FormaPagoDesembolso' => ['A_PLAZOS'], 'TipoOperacionDetalle' => 'PRÉSTAMO', 'IndividualMultiple' => 'INDIVIDUAL'],
            ],
            'DatosRegulatorios' => [
                'tipo_cambio' => 3.75,
                'oportunidad_pago' => 'AL_MOMENTO_OPERACION',
                'informacion_adicional' => 'Información validada por el oficial.',
                'persona_natural' => [
                    'tipo_via' => 'AVENIDA',
                    'numero_via' => '321',
                    'estado_civil' => 'CASADO',
                    'conyuge' => 'MARIA RAMOS',
                ],
            ],
        ]);

        $sheet = (new RopExcelExportService)->libro($rop)->getSheetByName('Modelo ROP');

        self::assertSame('ROP-000123', $sheet->getCell('C15')->getValue());
        self::assertSame(5000.0, $sheet->getCell('C25')->getValue());
        self::assertSame('PEREZ', $sheet->getCell('A95')->getValue());
        self::assertSame('12345678', $sheet->getCell('F97')->getValue());
        self::assertSame(3.75, $sheet->getCell('P25')->getValue());
        self::assertStringContainsString('X', (string) $sheet->getCell('A66')->getValue());
        self::assertSame('Información validada por el oficial.', $sheet->getCell('C68')->getValue());
        self::assertSame('AVENIDA', $sheet->getCell('C101')->getValue());
        self::assertSame('321', $sheet->getCell('I101')->getValue());
        self::assertStringContainsString('X', (string) $sheet->getCell('D104')->getValue());
        self::assertSame('MARIA RAMOS', $sheet->getCell('M103')->getValue());
    }
}
