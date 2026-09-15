<?php

namespace Tests\Unit;

use App\Models\RopRegistro;
use Tests\TestCase;

class RopRegistroStateTest extends TestCase
{
    public function test_ros_is_only_available_for_a_suspicious_qualified_rop_without_an_existing_ros(): void
    {
        $rop = new RopRegistro([
            'Estado' => RopRegistro::ESTADO_CALIFICADO,
            'Calificacion' => RopRegistro::CALIFICACION_SOSPECHOSA,
        ]);

        self::assertTrue($rop->puedeGenerarRos());

        $rop->Calificacion = null;
        self::assertFalse($rop->puedeGenerarRos());

        $rop->Calificacion = RopRegistro::CALIFICACION_INUSUAL;
        self::assertFalse($rop->puedeGenerarRos());

        $rop->Calificacion = RopRegistro::CALIFICACION_SOSPECHOSA;
        $rop->RosCasoID = 10;
        self::assertFalse($rop->puedeGenerarRos());
    }
}
