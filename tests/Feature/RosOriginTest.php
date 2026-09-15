<?php

namespace Tests\Feature;

use App\Models\RosCaso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosOriginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ros_can_be_created_directly_without_a_rop(): void
    {
        $ros = RosCaso::create([
            'SedeID' => 1,
            'Estado' => RosCaso::ESTADO_BORRADOR,
        ]);

        self::assertNull($ros->RopRegistroID);
        self::assertSame(RosCaso::ORIGEN_DIRECTO, $ros->OrigenRos);
    }

    public function test_a_ros_with_a_rop_keeps_that_origin(): void
    {
        $ros = RosCaso::create([
            'SedeID' => 1,
            'RopRegistroID' => 123,
            'Estado' => RosCaso::ESTADO_BORRADOR,
        ]);

        self::assertSame(123, $ros->RopRegistroID);
        self::assertSame(RosCaso::ORIGEN_ROP, $ros->OrigenRos);
    }
}
