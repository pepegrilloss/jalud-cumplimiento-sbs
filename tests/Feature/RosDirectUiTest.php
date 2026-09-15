<?php

namespace Tests\Feature;

use App\Models\RosCaso;
use App\Models\User;
use App\Services\SedeContext;
use Database\Seeders\CumplimientoAccessSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RosDirectUiTest extends TestCase
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
        Schema::connection('jalud')->create('Zona', function (Blueprint $table): void {
            $table->increments('ZonaID');
            $table->unsignedInteger('SedeID');
            $table->string('Nombre');
            $table->boolean('Activo')->default(true);
        });
        Schema::connection('jalud')->create('Cliente', function (Blueprint $table): void {
            $table->increments('ClienteID');
            $table->unsignedInteger('SedeID');
            $table->string('DNI')->nullable();
            $table->string('NombresApellidos')->nullable();
            $table->boolean('Activo')->default(true);
        });
        Schema::connection('jalud')->create('Credito', function (Blueprint $table): void {
            $table->increments('CreditoID');
            $table->unsignedInteger('SedeID');
            $table->unsignedInteger('ProposicionCreditoID');
        });
        Schema::connection('jalud')->create('ProposicionCredito', function (Blueprint $table): void {
            $table->increments('ProposicionCreditoID');
            $table->unsignedInteger('SedeID');
            $table->unsignedInteger('ClienteID');
            $table->string('CodigoCredito')->nullable();
        });
        Schema::connection('jalud')->create('Pago', function (Blueprint $table): void {
            $table->increments('PagoID');
            $table->unsignedInteger('SedeID');
            $table->unsignedInteger('CreditoID');
            $table->boolean('Activo')->default(true);
        });
        DB::connection('jalud')->table('Sede')->insert([
            'SedeID' => 1, 'Nombre' => 'Chiclayo', 'Codigo' => 'CHI', 'Activo' => true,
        ]);
    }

    public function test_the_create_screen_identifies_a_direct_ros_and_offers_jalud_prefill(): void
    {
        $this->seed(CumplimientoAccessSeeder::class);
        $user = User::create([
            'name' => 'Administrador',
            'username' => 'ROS_ADMIN',
            'password' => 'secret',
            'SedeID' => 1,
        ]);
        foreach (['create_ros::caso', 'view_any_ros::caso', 'view_ros::caso'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->assignRole(Role::findByName('super_admin'));
        $user->givePermissionTo(['create_ros::caso', 'view_any_ros::caso', 'view_ros::caso']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($user);
        app(SedeContext::class)->seleccionar($user, 1);

        self::assertTrue($user->can('create_ros::caso'));
        self::assertTrue(Gate::forUser($user)->allows('create', RosCaso::class));

        $this->get('/cumplimiento/ros-casos/create')
            ->assertOk()
            ->assertSee('Origen del ROS')
            ->assertSee('Directo desde JALUD')
            ->assertSee('¿A quién corresponde el caso ROS?')
            ->assertSee('Trabajador')
            ->assertSee('Trabajador para precarga')
            ->assertSee('Cliente para precarga')
            ->assertSee('Crédito para trazabilidad')
            ->assertSee('Pago para trazabilidad');
    }
}
