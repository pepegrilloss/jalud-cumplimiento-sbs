<?php
namespace Tests\Feature;
use App\Models\DeclaracionTrabajador;
use App\Models\User;
use App\Policies\DeclaracionTrabajadorPolicy;
use Mockery;
use Tests\TestCase;
class DeclaracionPermissionsTest extends TestCase
{
 public function test_no_permite_editar_una_declaracion_finalizada_aunque_tenga_permiso():void{$u=Mockery::mock(User::class)->makePartial();$u->shouldReceive('hasRole')->with('super_admin')->andReturnFalse();$u->shouldReceive('can')->with('editar_declaraciones_trabajador')->andReturnTrue();$d=new DeclaracionTrabajador(['Estado'=>'FINALIZADO']);self::assertFalse((new DeclaracionTrabajadorPolicy)->update($u,$d));}
 protected function tearDown():void{Mockery::close();parent::tearDown();}
}
