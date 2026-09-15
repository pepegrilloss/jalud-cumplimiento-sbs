<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CumplimientoAccessSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShieldRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.jalud', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('jalud');
        Schema::connection('jalud')->create('Sede', function (Blueprint $table): void {
            $table->increments('SedeID');
            $table->string('Nombre');
            $table->string('Codigo')->nullable();
            $table->boolean('Activo')->default(true);
        });
        DB::connection('jalud')->table('Sede')->insert([
            'SedeID' => 1, 'Nombre' => 'Chiclayo 01', 'Codigo' => 'CH01', 'Activo' => true,
        ]);
    }

    public function test_super_admin_can_access_shield_role_management(): void
    {
        $this->seed(CumplimientoAccessSeeder::class);

        $administrador = User::create([
            'name' => 'Administrador', 'username' => 'ADMIN_SHIELD', 'password' => 'secret', 'SedeID' => 1,
        ]);
        $administrador->assignRole(Role::findByName('super_admin'));

        $this->actingAs($administrador)->withSession(['sede_activa' => 1])
            ->get('/cumplimiento/shield/roles')
            ->assertOk()
            ->assertSee('Roles');
    }

    public function test_operational_user_cannot_access_shield_role_management(): void
    {
        $this->seed(CumplimientoAccessSeeder::class);

        $oficial = User::create([
            'name' => 'Oficial', 'username' => 'OFICIAL_SHIELD', 'password' => 'secret', 'SedeID' => 1,
        ]);
        $oficial->assignRole(Role::findByName('oficial_cumplimiento_sbs'));

        $this->actingAs($oficial)->withSession(['sede_activa' => 1])
            ->get('/cumplimiento/shield/roles')
            ->assertForbidden();
    }
}
