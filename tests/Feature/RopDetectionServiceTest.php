<?php

namespace Tests\Feature;

use App\Models\RopAuditoria;
use App\Services\Rop\RopAuditService;
use App\Services\Rop\RopDetectionService;
use App\Services\Rop\RopSnapshotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class RopDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.jalud', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('jalud');

        Schema::connection('jalud')->create('Cliente', function (Blueprint $table): void {
            $table->increments('ClienteID');
            $table->unsignedBigInteger('SedeID');
            $table->string('NombresApellidos');
            $table->string('DNI')->nullable();
        });
        Schema::connection('jalud')->create('ProposicionCredito', function (Blueprint $table): void {
            $table->increments('ProposicionCreditoID');
            $table->unsignedInteger('ClienteID');
            $table->unsignedBigInteger('SedeID');
            $table->unsignedBigInteger('ZonaID')->nullable();
            $table->string('CodigoCredito');
            $table->decimal('MontoTotal', 14, 2);
            $table->string('Estado');
            $table->boolean('Eliminado')->default(false);
        });
        Schema::connection('jalud')->create('Credito', function (Blueprint $table): void {
            $table->increments('CreditoID');
            $table->unsignedInteger('ProposicionCreditoID');
            $table->unsignedBigInteger('SedeID');
            $table->dateTime('FechaGeneracion');
        });
    }

    public function test_second_detection_creates_no_duplicate_rop(): void
    {
        DB::table('ros_configuraciones')->insert([
            'SedeID' => 1,
            'UmbralRop' => 2000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('jalud')->table('Cliente')->insert([
            'ClienteID' => 1,
            'SedeID' => 1,
            'NombresApellidos' => 'CLIENTE PRUEBA',
            'DNI' => '12345678',
        ]);
        DB::connection('jalud')->table('ProposicionCredito')->insert([
            'ProposicionCreditoID' => 1,
            'ClienteID' => 1,
            'SedeID' => 1,
            'ZonaID' => null,
            'CodigoCredito' => 'C-000001',
            'MontoTotal' => 2000,
            'Estado' => 'APROBADO',
            'Eliminado' => false,
        ]);
        DB::connection('jalud')->table('Credito')->insert([
            'CreditoID' => 1,
            'ProposicionCreditoID' => 1,
            'SedeID' => 1,
            'FechaGeneracion' => '2026-09-10 10:00:00',
        ]);

        $this->mock(RopSnapshotService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('crear')->once()->andReturn([]);
        });
        $this->mock(RopAuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('registrar')->once()->andReturn(new RopAuditoria);
        });

        $primera = app(RopDetectionService::class)->detectar(
            now()->setDate(2026, 9, 1),
            now()->setDate(2026, 9, 30),
            1,
        );
        $segunda = app(RopDetectionService::class)->detectar(
            now()->setDate(2026, 9, 1),
            now()->setDate(2026, 9, 30),
            1,
        );

        self::assertSame(1, $primera->creados);
        self::assertSame(0, $segunda->creados);
        self::assertSame(1, $segunda->duplicados);
        self::assertDatabaseCount('rop_registros', 1);
    }

    public function test_detection_with_one_date_only_loads_credits_generated_that_day(): void
    {
        DB::table('ros_configuraciones')->insert([
            'SedeID' => 1,
            'UmbralRop' => 2000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('jalud')->table('Cliente')->insert([
            'ClienteID' => 1,
            'SedeID' => 1,
            'NombresApellidos' => 'CLIENTE PRUEBA',
            'DNI' => '12345678',
        ]);
        DB::connection('jalud')->table('ProposicionCredito')->insert([
            [
                'ProposicionCreditoID' => 1, 'ClienteID' => 1, 'SedeID' => 1, 'ZonaID' => null,
                'CodigoCredito' => 'C-000001', 'MontoTotal' => 2000, 'Estado' => 'APROBADO', 'Eliminado' => false,
            ],
            [
                'ProposicionCreditoID' => 2, 'ClienteID' => 1, 'SedeID' => 1, 'ZonaID' => null,
                'CodigoCredito' => 'C-000002', 'MontoTotal' => 3000, 'Estado' => 'APROBADO', 'Eliminado' => false,
            ],
        ]);
        DB::connection('jalud')->table('Credito')->insert([
            [
                'CreditoID' => 1, 'ProposicionCreditoID' => 1, 'SedeID' => 1,
                'FechaGeneracion' => '2026-07-11 00:00:00',
            ],
            [
                'CreditoID' => 2, 'ProposicionCreditoID' => 2, 'SedeID' => 1,
                'FechaGeneracion' => '2026-07-12 00:00:00',
            ],
        ]);

        $this->mock(RopSnapshotService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('crear')->once()->andReturn([]);
        });
        $this->mock(RopAuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('registrar')->once()->andReturn(new RopAuditoria);
        });

        $resultado = app(RopDetectionService::class)->detectar(
            now()->setDate(2026, 7, 11),
            now()->setDate(2026, 7, 11),
            1,
        );

        self::assertSame(1, $resultado->creados);
        self::assertDatabaseHas('rop_registros', ['CreditoID' => 1]);
        self::assertDatabaseMissing('rop_registros', ['CreditoID' => 2]);
    }
}
