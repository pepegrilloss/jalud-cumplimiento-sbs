<?php

namespace Tests\Feature;

use App\Models\DeclaracionTrabajador;
use App\Models\User;
use App\Policies\DeclaracionTrabajadorPolicy;
use Mockery;
use Tests\TestCase;

class DeclaracionPermissionsTest extends TestCase
{
    public function test_no_permite_editar_una_declaracion_finalizada_aunque_tenga_permiso(): void
    {
        $u = Mockery::mock(User::class)->makePartial();
        $u->shouldReceive('hasRole')->with('super_admin')->andReturnFalse();
        $u->shouldReceive('can')->with('editar_declaraciones_trabajador')->andReturnTrue();
        $d = new DeclaracionTrabajador(['Estado' => 'FINALIZADO']);
        self::assertFalse((new DeclaracionTrabajadorPolicy)->update($u, $d));
    }

    public function test_super_admin_puede_finalizar_y_generar_anexos(): void
    {
        $u = Mockery::mock(User::class)->makePartial();
        $u->shouldReceive('hasRole')->with('super_admin')->andReturnTrue();
        $d = new DeclaracionTrabajador(['Estado' => 'BORRADOR']);
        $policy = new DeclaracionTrabajadorPolicy;
        self::assertTrue($policy->finalizar($u, $d));
        self::assertTrue($policy->generarPdf($u, $d));
        self::assertTrue($policy->nuevaVersion($u, $d));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
