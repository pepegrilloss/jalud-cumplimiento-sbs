<?php

namespace Tests\Unit;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

class RosTemplateTest extends TestCase
{
    public function test_the_official_template_is_packaged_and_readable(): void
    {
        $ruta = dirname(__DIR__, 2).'/resources/templates/ros/modelo-ros-sbs.xls';
        self::assertFileExists($ruta);

        $libro = IOFactory::load($ruta);
        self::assertNotNull($libro->getSheetByName('FORMATO'));
        self::assertSame('REPORTE DE OPERACIONES SOSPECHOSAS', $libro->getSheetByName('FORMATO')->getCell('A2')->getValue());
    }
}
