<?php

namespace Tests\Feature;

use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource;
use App\Models\Cliente;
use App\Models\DeclaracionCliente;
use App\Models\DeclaracionTrabajador;
use App\Models\LogAuditoria;
use App\Models\Trabajador;
use App\Models\TrabajadorArchivo;
use App\Models\User;
use App\Services\Declaraciones\EstadoPersonaAuditService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DeclaracionesListadoAgrupadoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('dj_trabajadores', function (Blueprint $table): void {
            $table->bigIncrements('TrabajadorID');
            $table->integer('SedeID');
            $table->string('Nombres');
            $table->string('ApellidoPaterno')->nullable();
            $table->string('ApellidoMaterno')->nullable();
            $table->string('NumeroDocumento');
            $table->boolean('Activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dj_declaraciones_trabajador', function (Blueprint $table): void {
            $table->bigIncrements('DeclaracionTrabajadorID');
            $table->unsignedBigInteger('TrabajadorID');
            $table->integer('SedeID');
            $table->unsignedInteger('NumeroVersion');
            $table->string('Estado');
            $table->date('FechaContratacion')->nullable();
            $table->string('Nombres');
            $table->string('ApellidoPaterno')->nullable();
            $table->string('ApellidoMaterno')->nullable();
            $table->string('NumeroDocumento');
            $table->string('Cargo')->nullable();
            $table->date('FechaDeclaracion')->nullable();
            $table->timestamps();
        });

        Schema::create('Cliente', function (Blueprint $table): void {
            $table->increments('ClienteID');
            $table->integer('SedeID');
            $table->boolean('Activo')->default(true);
        });

        Schema::create('dj_declaraciones_cliente', function (Blueprint $table): void {
            $table->bigIncrements('DeclaracionClienteID');
            $table->integer('ClienteID');
            $table->integer('SedeID');
            $table->unsignedInteger('NumeroVersion');
            $table->string('Estado');
            $table->string('Nombres');
            $table->string('Apellidos');
            $table->string('NumeroDocumento');
            $table->string('Ocupacion')->nullable();
            $table->date('FechaDeclaracion')->nullable();
            $table->timestamps();
        });

        Schema::create('dj_trabajador_archivos', function (Blueprint $table): void {
            $table->bigIncrements('TrabajadorArchivoID');
            $table->unsignedBigInteger('TrabajadorID');
            $table->integer('SedeID');
            $table->string('TipoDocumento');
            $table->string('Titulo');
            $table->text('Descripcion')->nullable();
            $table->string('RutaArchivo');
            $table->string('NombreOriginal')->nullable();
            $table->bigInteger('SubidoPorID')->nullable();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('accion');
            $table->string('modelo');
            $table->unsignedBigInteger('modelo_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('platform')->nullable();
            $table->unsignedBigInteger('SedeID')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('dj_trabajador_archivos');
        Schema::dropIfExists('dj_declaraciones_cliente');
        Schema::dropIfExists('Cliente');
        Schema::dropIfExists('dj_declaraciones_trabajador');
        Schema::dropIfExists('dj_trabajadores');
        Schema::dropIfExists('logs');

        parent::tearDown();
    }

    public function test_listado_de_trabajadores_conserva_solo_la_ultima_version_por_persona(): void
    {
        DB::table('dj_trabajadores')->insert([
            ['TrabajadorID' => 1, 'SedeID' => 1, 'Nombres' => 'Ana', 'ApellidoPaterno' => 'Pérez', 'NumeroDocumento' => '11111111', 'Activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['TrabajadorID' => 2, 'SedeID' => 1, 'Nombres' => 'Luis', 'ApellidoPaterno' => 'Díaz', 'NumeroDocumento' => '22222222', 'Activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('dj_declaraciones_trabajador')->insert([
            $this->declaracionTrabajador(1, 1, 1, 'Ana', '11111111'),
            $this->declaracionTrabajador(2, 1, 2, 'Ana', '11111111'),
            $this->declaracionTrabajador(3, 2, 1, 'Luis', '22222222'),
        ]);

        $registros = DeclaracionTrabajadorResource::prepararListado(
            DeclaracionTrabajador::withoutGlobalScope('sede'),
        )->orderBy('TrabajadorID')->get();

        self::assertCount(2, $registros);
        self::assertSame([2, 1], $registros->pluck('NumeroVersion')->all());
        self::assertSame('Ana Pérez', $registros->first()->trabajador->NombreCompleto);
        self::assertSame(2, $registros->first()->versiones_count);
        self::assertCount(2, $registros->first()->versiones);
    }

    public function test_listado_de_clientes_conserva_solo_la_ultima_version_por_persona(): void
    {
        DB::table('Cliente')->insert([
            ['ClienteID' => 10, 'SedeID' => 1, 'Activo' => true],
            ['ClienteID' => 20, 'SedeID' => 1, 'Activo' => true],
        ]);

        DB::table('dj_declaraciones_cliente')->insert([
            $this->declaracionCliente(1, 10, 1, 'María', '11111111'),
            $this->declaracionCliente(2, 10, 2, 'María', '11111111'),
            $this->declaracionCliente(3, 20, 1, 'Rosa', '22222222'),
        ]);

        $registros = DeclaracionClienteResource::prepararListado(
            DeclaracionCliente::withoutGlobalScope('sede'),
        )->orderBy('ClienteID')->get();

        self::assertCount(2, $registros);
        self::assertSame([2, 1], $registros->pluck('NumeroVersion')->all());
        self::assertTrue((bool) $registros->first()->cliente->Activo);
        self::assertSame(2, $registros->first()->versiones_count);
        self::assertCount(2, $registros->first()->versiones);
    }

    public function test_el_titulo_del_detalle_utiliza_el_nombre_y_no_el_documento(): void
    {
        $trabajador = new DeclaracionTrabajador([
            'Nombres' => 'Julia',
            'ApellidoPaterno' => 'Torres',
            'ApellidoMaterno' => 'Ríos',
            'NumeroDocumento' => '12345678',
        ]);
        $cliente = new DeclaracionCliente([
            'Nombres' => 'Noemí',
            'Apellidos' => 'Caro Díaz',
            'NumeroDocumento' => '87654321',
        ]);

        self::assertSame('Julia Torres Ríos', DeclaracionTrabajadorResource::getRecordTitle($trabajador));
        self::assertSame('Noemí Caro Díaz', DeclaracionClienteResource::getRecordTitle($cliente));
    }

    public function test_documento_adicional_pertenece_al_trabajador_y_se_elimina_del_disco(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('declaraciones/trabajadores/adjuntos/convenio.pdf', 'archivo');

        DB::table('dj_trabajadores')->insert([
            'TrabajadorID' => 7,
            'SedeID' => 3,
            'Nombres' => 'Elena',
            'ApellidoPaterno' => 'Vega',
            'NumeroDocumento' => '33333333',
            'Activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $archivo = TrabajadorArchivo::create([
            'TrabajadorID' => 7,
            'TipoDocumento' => 'CONVENIO',
            'Titulo' => 'Convenio de abono',
            'RutaArchivo' => 'declaraciones/trabajadores/adjuntos/convenio.pdf',
            'NombreOriginal' => 'convenio.pdf',
        ]);

        self::assertSame(3, $archivo->SedeID);
        self::assertSame('Elena Vega', $archivo->trabajador->NombreCompleto);

        $archivo->delete();
        Storage::disk('local')->assertMissing('declaraciones/trabajadores/adjuntos/convenio.pdf');
    }

    public function test_baja_y_reactivacion_dejan_un_historial_auditable_completo(): void
    {
        $usuario = Mockery::mock(User::class)->makePartial();
        $usuario->setRawAttributes(['id' => 77]);
        $usuario->shouldReceive('hasRole')->with('super_admin')->andReturnTrue();
        $this->app['auth']->login($usuario);

        DB::table('dj_trabajadores')->insert([
            'TrabajadorID' => 9,
            'SedeID' => 1,
            'Nombres' => 'Elena',
            'ApellidoPaterno' => 'Vega',
            'NumeroDocumento' => '33333333',
            'Activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $trabajador = Trabajador::withoutGlobalScope('sede')->findOrFail(9);
        $servicio = app(EstadoPersonaAuditService::class);
        $servicio->cambiar($trabajador, false, 'Fin de la relación laboral.');
        $servicio->cambiar($trabajador->fresh(), true, 'Se regularizó su contratación.');

        $historial = LogAuditoria::query()->where('modelo', 'Trabajador')->where('modelo_id', 9)->orderBy('id')->get();

        self::assertTrue((bool) $trabajador->fresh()->Activo);
        self::assertCount(2, $historial);
        self::assertSame('DAR_DE_BAJA', $historial->first()->accion);
        self::assertTrue($historial->first()->old_values['activo']);
        self::assertFalse($historial->first()->new_values['activo']);
        self::assertSame('Fin de la relación laboral.', $historial->first()->new_values['motivo']);
        self::assertSame(77, $historial->first()->user_id);
        self::assertSame('REACTIVAR', $historial->last()->accion);
        self::assertTrue($historial->last()->new_values['activo']);

        DB::table('Cliente')->insert(['ClienteID' => 31, 'SedeID' => 1, 'Activo' => true]);
        $cliente = Cliente::withoutGlobalScope('sede')->findOrFail(31);
        $servicio->cambiar($cliente, false, 'Cierre de la relación comercial.');

        $historialCliente = LogAuditoria::query()->where('modelo', 'Cliente')->where('modelo_id', 31)->sole();
        self::assertSame('DAR_DE_BAJA', $historialCliente->accion);
        self::assertFalse($historialCliente->new_values['activo']);
        self::assertSame('Cierre de la relación comercial.', $historialCliente->new_values['motivo']);
    }

    private function declaracionTrabajador(int $id, int $trabajadorId, int $version, string $nombre, string $documento): array
    {
        return [
            'DeclaracionTrabajadorID' => $id,
            'TrabajadorID' => $trabajadorId,
            'SedeID' => 1,
            'NumeroVersion' => $version,
            'Estado' => 'FINALIZADO',
            'Nombres' => $nombre,
            'NumeroDocumento' => $documento,
            'created_at' => now()->addSeconds($id),
            'updated_at' => now()->addSeconds($id),
        ];
    }

    private function declaracionCliente(int $id, int $clienteId, int $version, string $nombre, string $documento): array
    {
        return [
            'DeclaracionClienteID' => $id,
            'ClienteID' => $clienteId,
            'SedeID' => 1,
            'NumeroVersion' => $version,
            'Estado' => 'FINALIZADO',
            'Nombres' => $nombre,
            'Apellidos' => 'Prueba',
            'NumeroDocumento' => $documento,
            'created_at' => now()->addSeconds($id),
            'updated_at' => now()->addSeconds($id),
        ];
    }
}
