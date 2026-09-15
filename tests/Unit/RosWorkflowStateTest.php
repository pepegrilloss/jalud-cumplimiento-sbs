<?php

namespace Tests\Unit;

use App\Models\RosCaso;
use App\Services\Ros\RosWorkflowService;
use PHPUnit\Framework\TestCase;

class RosWorkflowStateTest extends TestCase
{
    public function test_respects_the_expected_state_graph(): void
    {
        $service = new RosWorkflowService();
        $caso = new RosCaso();

        $caso->Estado = RosCaso::ESTADO_BORRADOR;
        self::assertTrue($service->puedeTransicionar($caso, RosCaso::ESTADO_EVALUACION));
        self::assertFalse($service->puedeTransicionar($caso, RosCaso::ESTADO_REPORTADO));

        $caso->Estado = RosCaso::ESTADO_EVALUACION;
        self::assertTrue($service->puedeTransicionar($caso, RosCaso::ESTADO_BORRADOR));
        self::assertTrue($service->puedeTransicionar($caso, RosCaso::ESTADO_DESCARTADO));
        self::assertTrue($service->puedeTransicionar($caso, RosCaso::ESTADO_APROBADO));

        $caso->Estado = RosCaso::ESTADO_APROBADO;
        self::assertTrue($service->puedeTransicionar($caso, RosCaso::ESTADO_REPORTADO));

        $caso->Estado = RosCaso::ESTADO_REPORTADO;
        foreach (array_keys(RosCaso::estados()) as $estado) {
            self::assertFalse($service->puedeTransicionar($caso, $estado));
        }
    }
}
