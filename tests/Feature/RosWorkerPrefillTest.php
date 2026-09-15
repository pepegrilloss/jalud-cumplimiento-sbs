<?php

namespace Tests\Feature;

use App\Models\DeclaracionTrabajador;
use App\Models\RosCaso;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\Ros\RosPrefillService;
use App\Services\SedeContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RosWorkerPrefillTest extends TestCase
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
            'SedeID' => 1, 'Nombre' => 'Chiclayo', 'Codigo' => 'CHI', 'Activo' => true,
        ]);

        $user = User::create(['name' => 'Admin', 'username' => 'ADMIN_ROS_WORKER', 'password' => 'secret']);
        $user->assignRole(Role::create(['name' => 'super_admin', 'guard_name' => 'web']));
        $this->actingAs($user);
        app(SedeContext::class)->seleccionar($user, 1);
        Trabajador::resetSedeFilterCache();
        DeclaracionTrabajador::resetSedeFilterCache();
    }

    public function test_worker_prefill_uses_the_latest_local_declaration(): void
    {
        $trabajador = Trabajador::create([
            'SedeID' => 1,
            'TipoDocumento' => 'DNI',
            'NumeroDocumento' => '12345678',
            'Nombres' => 'Ana',
            'ApellidoPaterno' => 'Perez',
            'ApellidoMaterno' => 'Lopez',
            'Activo' => true,
        ]);
        DeclaracionTrabajador::create($this->declaracion($trabajador, 1, ['Cargo' => 'Auxiliar']));
        DeclaracionTrabajador::create($this->declaracion($trabajador, 2, ['Cargo' => 'Analista']));

        $persona = app(RosPrefillService::class)->personasParaTrabajador($trabajador->getKey())[0];

        self::assertSame('Ana', $persona['Nombres']);
        self::assertSame('12345678', $persona['NumeroDocumento']);
        self::assertSame('Analista', $persona['ProfesionOcupacion']);
        self::assertSame('EMPLEADO', $persona['RelacionEmpresa']);
        self::assertTrue($persona['VinculadoActualmente']);
    }

    public function test_worker_ros_keeps_worker_traceability_without_jalud_credit_data(): void
    {
        $trabajador = Trabajador::create([
            'SedeID' => 1,
            'TipoDocumento' => 'DNI',
            'NumeroDocumento' => '87654321',
            'Nombres' => 'Luis',
            'ApellidoPaterno' => 'Ramos',
            'Activo' => true,
        ]);

        $ros = RosCaso::create([
            'SedeID' => 1,
            'TipoPersonaOrigen' => RosCaso::PERSONA_TRABAJADOR,
            'TrabajadorID' => $trabajador->getKey(),
            'Estado' => RosCaso::ESTADO_BORRADOR,
        ]);

        self::assertSame(RosCaso::PERSONA_TRABAJADOR, $ros->TipoPersonaOrigen);
        self::assertSame($trabajador->getKey(), $ros->TrabajadorID);
        self::assertNull($ros->ClienteID);
        self::assertNull($ros->CreditoID);
        self::assertNull($ros->PagoID);
    }

    private function declaracion(Trabajador $trabajador, int $version, array $changes = []): array
    {
        return array_replace([
            'TrabajadorID' => $trabajador->getKey(),
            'SedeID' => 1,
            'NumeroVersion' => $version,
            'Estado' => 'BORRADOR',
            'FechaContratacion' => '2026-01-01',
            'Nombres' => 'Ana',
            'ApellidoPaterno' => 'Perez',
            'ApellidoMaterno' => 'Lopez',
            'TipoDocumento' => 'DNI',
            'NumeroDocumento' => '12345678',
            'Nacionalidad' => 'Peruana',
            'EstadoCivil' => 'SOLTERO',
            'ProfesionOficio' => null,
            'CodigoOcupacion' => '001',
            'Cargo' => 'Auxiliar',
            'DomicilioVia' => 'Av. Principal',
            'DomicilioDistrito' => 'Chiclayo',
            'DomicilioProvincia' => 'Chiclayo',
            'DomicilioDepartamento' => 'Lambayeque',
            'CiudadSuscripcion' => 'Chiclayo',
            'FechaDeclaracion' => '2026-09-15',
            'CreadoPorID' => auth()->id(),
        ], $changes);
    }
}
