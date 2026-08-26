<?php

namespace Tests\Unit;

use App\Services\DniOcrTextExtractor;
use PHPUnit\Framework\TestCase;

class DniOcrTextExtractorTest extends TestCase
{
    public function test_extrae_dni_y_nombre_completo_combinando_lecturas(): void
    {
        $extractor = new DniOcrTextExtractor;

        $resultado = $extractor->extraer([
            <<<'OCR'
DOCUMENTO NACIONAL DE DENTIOS 72883083-3
j VILCHERREZ PURIZACA $ he.
=> Po JULIO MARIO da
M PER 06 12 2000
OCR,
            <<<'OCR'
72883083-8
VILCHERREZ PURIZACA
JULIO MARIO
M
PER
06 12 2000
SOLTERO
0213987384
OCR,
            <<<'OCR'
72883083-8
06122000
0213987384
OCR,
        ]);

        $this->assertSame('72883083', $resultado['dni']);
        $this->assertSame('VILCHERREZ PURIZACA JULIO MARIO', $resultado['nombres']);
    }

    public function test_no_confunde_fecha_compacta_ni_numero_de_tarjeta_con_dni(): void
    {
        $extractor = new DniOcrTextExtractor;

        $resultado = $extractor->extraer([
            "06122000\n0213987384\n18 09 2035",
        ]);

        $this->assertNull($resultado['dni']);
    }

    public function test_prioriza_dni_con_digito_verificador(): void
    {
        $extractor = new DniOcrTextExtractor;

        $resultado = $extractor->extraer([
            "12345678\n72883083-8\n06122000",
            "72883083-8",
        ]);

        $this->assertSame('72883083', $resultado['dni']);
    }
}
