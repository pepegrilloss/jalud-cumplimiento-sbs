<?php

namespace Tests\Feature;

use App\Models\RopPeriodo;
use App\Models\RopRegistro;
use App\Models\RosCaso;
use App\Models\User;
use Database\Seeders\CumplimientoAccessSeeder;
use Database\Seeders\CumplimientoSbsPermisosSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Unit\RopCompletenessServiceTest;

class RopUiTest extends TestCase
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
        Schema::connection('jalud')->create('Zona', function (Blueprint $table): void {
            $table->increments('ZonaID');
            $table->unsignedInteger('CiudadID')->nullable();
            $table->string('Nombre');
            $table->boolean('Activo')->default(true);
            $table->dateTime('FechaCreacion')->nullable();
            $table->dateTime('FechaModificacion')->nullable();
            $table->unsignedInteger('SedeID')->nullable();
        });
    }

    public function test_super_admin_can_render_the_automatic_rop_inbox(): void
    {
        $this->seed(CumplimientoAccessSeeder::class);
        $role = Role::findByName('super_admin');
        $user = User::create(['name' => 'Administrador', 'username' => 'ROP_ADMIN', 'password' => 'secret', 'SedeID' => 1]);
        $user->assignRole($role);

        $periodo = RopPeriodo::create(['SedeID' => 1, 'Periodo' => '2026-09-01', 'Estado' => RopPeriodo::ESTADO_ABIERTO]);
        $rop = RopRegistro::create([
            'NumeroRop' => 'ROP-000001', 'RopPeriodoID' => $periodo->getKey(), 'SedeID' => 1,
            'ClienteID' => 1, 'ProposicionCreditoID' => 1, 'CreditoID' => 1, 'CodigoCredito' => 'C-1',
            'ClienteNombre' => 'CLIENTE PRUEBA', 'NumeroDocumento' => '12345678', 'Periodo' => '2026-09-01',
            'FechaOperacion' => '2026-09-10 10:00:00', 'Monto' => 5000, 'UmbralAplicado' => 2000,
            'Moneda' => 'PEN', 'Estado' => RopRegistro::ESTADO_PENDIENTE,
            'SnapshotDatos' => ['cliente' => ['NombresApellidos' => 'CLIENTE PRUEBA'], 'pagos' => [], 'garantia' => null],
        ]);

        $this->actingAs($user)->withSession(['sede_activa' => 1])
            ->get('/cumplimiento/rop-registros')
            ->assertOk()
            ->assertSee('Registro de Operaciones (ROP)')
            ->assertSee('Cargar créditos por fecha')
            ->assertSee('Completar ROP')
            ->assertDontSee('Más acciones')
            ->assertDontSee('Revisar/Calificar');

        $this->get("/cumplimiento/rop-registros/{$rop->getKey()}")
            ->assertOk()
            ->assertSee('Información del ROP')
            ->assertSee('Pagos relacionados')
            ->assertSee('Auditoría');

        $this->get("/cumplimiento/rop-registros/{$rop->getKey()}/completar")
            ->assertOk()
            ->assertSeeText('Completar ROP-000001')
            ->assertSee('Información del ROP y del sujeto obligado')
            ->assertSee('Información de la operación')
            ->assertSee('Datos de identificación del cliente');

        $this->get("/cumplimiento/rop/{$rop->getKey()}/reporte.xlsx")
            ->assertRedirect()
            ->assertSessionHasErrors('DatosRegulatorios');

        $rop->update(['DatosRegulatorios' => RopCompletenessServiceTest::datosCompletos()]);

        $this->get('/cumplimiento/rop-registros')
            ->assertOk()
            ->assertSee('Editar ROP')
            ->assertSee('Más acciones')
            ->assertSee('Revisar/Calificar')
            ->assertSee('Excel');

        $this->get("/cumplimiento/rop-registros/{$rop->getKey()}/completar")
            ->assertOk()
            ->assertSeeText('Editar ROP-000001');

        $rop->update([
            'Estado' => RopRegistro::ESTADO_CALIFICADO,
            'Calificacion' => RopRegistro::CALIFICACION_SOSPECHOSA,
            'CalificadoEn' => now(),
        ]);

        $this->get('/cumplimiento/rop-registros')
            ->assertOk()
            ->assertSee('Editar calificación')
            ->assertSee('Generar ROS')
            ->assertDontSee('Revisar/Calificar');

        $this->get("/cumplimiento/rop-registros/{$rop->getKey()}")
            ->assertOk()
            ->assertSee('Editar calificación')
            ->assertSee('Generar ROS')
            ->assertDontSee('Revisar/Calificar');

        $ros = RosCaso::create([
            'RopRegistroID' => $rop->getKey(),
            'SedeID' => 1,
            'Estado' => RosCaso::ESTADO_BORRADOR,
        ]);
        $rop->update(['RosCasoID' => $ros->getKey()]);

        $this->get('/cumplimiento/rop-registros')
            ->assertOk()
            ->assertDontSee('Generar ROS');

        $this->get("/cumplimiento/rop-registros/{$rop->getKey()}")
            ->assertOk()
            ->assertDontSee('Generar ROS');

        $this->get("/cumplimiento/rop/{$rop->getKey()}/reporte.xlsx")
            ->assertOk()
            ->assertDownload('ROP-000001.xlsx');

        $this->get('/cumplimiento/rop-catalogo-items')
            ->assertOk()
            ->assertSee('Tablas de mantenimiento ROP');

        $this->get('/cumplimiento/reportes-rop-ros')
            ->assertOk()
            ->assertSee('Reportes ROP y ROS')
            ->assertSee('Descargar ZIP');
    }

    public function test_existing_super_admin_receives_the_new_complete_rop_permission(): void
    {
        $role = Role::findOrCreate('super_admin', 'web');
        $user = User::create(['name' => 'Administrador', 'username' => 'ADMIN_SBS', 'password' => 'secret', 'SedeID' => 1]);
        $user->assignRole($role);

        $this->seed(CumplimientoSbsPermisosSeeder::class);

        self::assertTrue($user->fresh()->can('completar_rop'));
        self::assertTrue($user->fresh()->can('descargar_reportes_rop_ros'));
    }
}
