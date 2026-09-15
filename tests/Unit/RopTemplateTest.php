<?php

namespace Tests\Unit;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

class RopTemplateTest extends TestCase
{
    public function test_the_rop_template_is_packaged_and_readable(): void
    {
        $path = dirname(__DIR__, 2).'/resources/templates/rop/modelo-rop.xlsx';

        self::assertFileExists($path);
        $sheet = IOFactory::load($path)->getSheetByName('Modelo ROP');

        self::assertNotNull($sheet);
        self::assertSame('MODELO DE FORMATO DEL REGISTRO DE OPERACIONES (ROP)', $sheet->getCell('A10')->getValue());
        self::assertSame('Datos de identificación del CLIENTE', $sheet->getCell('B70')->getValue());
    }
}
