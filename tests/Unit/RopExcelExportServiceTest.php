<?php

namespace Tests\Unit;

use App\Models\RopRegistro;
use App\Models\RopCatalogoItem;
use App\Services\Rop\RopExcelExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RopExcelExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_the_official_template_from_the_historical_snapshot(): void
    {
        RopCatalogoItem::query()->create([
            'Tipo' => RopCatalogoItem::TIPO_SUJETO_OBLIGADO,
            'Codigo' => 'EMPRESA_SISTEMA_FINANCIERO',
            'Nombre' => 'Empresa del Sistema Financiero',
            'Activo' => true,
            'Orden' => 1,
        ]);

        $rop = new RopRegistro([
            'NumeroRop' => 'ROP-000123',
            'FechaOperacion' => '2026-09-10 08:30:00',
            'Monto' => 5000,
            'Moneda' => 'PEN',
            'SnapshotDatos' => [
                'configuracion' => ['EnteSupervisor' => 'SBS', 'CodigoEmpresa' => 'EMP-1', 'CodigoOficialCumplimiento' => 'OC-1', 'TipoSujetoObligado' => 'EMPRESA_SISTEMA_FINANCIERO'],
                'cliente' => ['ApellidoPaterno' => 'PEREZ', 'ApellidoMaterno' => 'LOPEZ', 'Nombres' => 'JUAN', 'TipoDocumento' => 'DNI', 'NumeroDocumento' => '12345678'],
                'operacion' => ['TipoOperacion' => 'OTRO', 'FormaPagoDesembolso' => ['A_PLAZOS'], 'TipoOperacionDetalle' => 'PRÉSTAMO', 'IndividualMultiple' => 'INDIVIDUAL'],
            ],
            'DatosRegulatorios' => [
                'tipo_sujeto_obligado' => 'EMPRESA_SISTEMA_FINANCIERO',
                // Guardar otra selección no debe borrar la categoría inferida del crédito JALUD.
                'tipos_operacion' => ['ACTO_NOTARIAL'],
                'tipo_operacion_otro' => null,
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

        $sheet = (new RopExcelExportService)->libro($rop)->getSheetByName('Registro de Operaciones');

        self::assertSame('ROP-000123', $sheet->getCell('C15')->getValue());
        self::assertSame(5000.0, $sheet->getCell('C25')->getValue());
        self::assertSame('PEREZ', $sheet->getCell('A95')->getValue());
        self::assertSame('12345678', $sheet->getCell('F97')->getValue());
        self::assertSame('Casa de préstamos', $sheet->getCell('P18')->getValue());
        self::assertSame(3.75, $sheet->getCell('P25')->getValue());
        self::assertStringContainsString('X', (string) $sheet->getCell('A66')->getValue());
        self::assertStringContainsString('X', (string) $sheet->getCell('A39')->getValue());
        self::assertSame('Información validada por el oficial.', $sheet->getCell('C68')->getValue());
        self::assertSame('AVENIDA', $sheet->getCell('C101')->getValue());
        self::assertSame('321', $sheet->getCell('I101')->getValue());
        self::assertStringContainsString('X', (string) $sheet->getCell('D104')->getValue());
        self::assertSame('MARIA RAMOS', $sheet->getCell('M103')->getValue());
        self::assertSame('REGISTRO DE OPERACIONES (ROP)', $sheet->getCell('A9')->getValue());
        self::assertSame('CORRELATIVO: ROP-000123', $sheet->getCell('L9')->getValue());
        self::assertSame(12.0, $sheet->getStyle('L9')->getFont()->getSize());
        self::assertSame('FF000000', $sheet->getStyle('L9')->getFont()->getColor()->getARGB());
        self::assertContains('A9:K9', $sheet->getMergeCells());
        self::assertContains('L9:Q9', $sheet->getMergeCells());
        self::assertFalse($sheet->getRowDimension(10)->getVisible());
        self::assertStringContainsString('w. Otro (especifique): PRÉSTAMO', (string) $sheet->getCell('M46')->getValue());
        self::assertSame('A9:Q104', $sheet->getPageSetup()->getPrintArea());
        self::assertFalse($sheet->getRowDimension(1)->getVisible());
        self::assertFalse($sheet->getRowDimension(8)->getVisible());
        self::assertTrue($sheet->getRowDimension(9)->getVisible());
        self::assertFalse($sheet->getRowDimension(106)->getVisible());
        self::assertFalse($sheet->getRowDimension(349)->getVisible());
        self::assertSame('TABLAS PARA EL LLENADO DEL MODELO DE FORMATO DEL REGISTRO DE OPERACIONES (ROP)', $sheet->getCell('A106')->getValue());

        $rutaTemporal = tempnam(sys_get_temp_dir(), 'rop-export-');

        try {
            (new Xlsx($sheet->getParent()))->save($rutaTemporal);
            $hojaExportada = IOFactory::load($rutaTemporal)->getSheetByName('Registro de Operaciones');

            self::assertSame('A9:Q104', $hojaExportada->getPageSetup()->getPrintArea());
            self::assertFalse($hojaExportada->getRowDimension(1)->getVisible());
            self::assertFalse($hojaExportada->getRowDimension(8)->getVisible());
            self::assertFalse($hojaExportada->getRowDimension(106)->getVisible());
        } finally {
            @unlink($rutaTemporal);
        }
    }
}
