<?php

namespace Tests\Feature;

use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource;
use App\Models\DeclaracionCliente;
use App\Models\DeclaracionTrabajador;
use App\Models\TrabajadorArchivo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('dj_trabajador_archivos');
        Schema::dropIfExists('dj_declaraciones_cliente');
        Schema::dropIfExists('Cliente');
        Schema::dropIfExists('dj_declaraciones_trabajador');
        Schema::dropIfExists('dj_trabajadores');

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

        $registros = DeclaracionTrabajadorResource::soloUltimaVersion(
            DeclaracionTrabajador::withoutGlobalScope('sede'),
        )->orderBy('TrabajadorID')->get();

        self::assertCount(2, $registros);
        self::assertSame([2, 1], $registros->pluck('NumeroVersion')->all());
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

        $registros = DeclaracionClienteResource::soloUltimaVersion(
            DeclaracionCliente::withoutGlobalScope('sede'),
        )->orderBy('ClienteID')->get();

        self::assertCount(2, $registros);
        self::assertSame([2, 1], $registros->pluck('NumeroVersion')->all());
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
