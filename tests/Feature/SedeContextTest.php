<?php

namespace Tests\Feature;

use App\Models\RosConfiguracion;
use App\Models\User;
use App\Services\SedeContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SedeContextTest extends TestCase
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
            ['SedeID' => 1, 'Nombre' => 'Chiclayo 01', 'Codigo' => 'CH01', 'Activo' => true],
            ['SedeID' => 2, 'Nombre' => 'Trujillo 01', 'Codigo' => 'TR01', 'Activo' => true],
            ['SedeID' => 3, 'Nombre' => 'Gerencia', 'Codigo' => 'GER', 'Activo' => true],
        ]);
    }

    public function test_super_admin_can_select_only_operational_sites(): void
    {
        $user = User::create(['name' => 'Admin', 'username' => 'ADMIN_SEDES', 'password' => 'secret']);
        $user->assignRole(Role::create(['name' => 'super_admin', 'guard_name' => 'web']));
        $this->actingAs($user);

        $context = app(SedeContext::class);

        self::assertSame([1, 2], $context->sedesPermitidas($user)->pluck('SedeID')->all());
        $this->expectException(ValidationException::class);
        $context->seleccionar($user, 3);
    }

    public function test_records_and_threshold_are_scoped_to_the_active_site(): void
    {
        $user = User::create(['name' => 'Admin', 'username' => 'ADMIN_UMBRAL', 'password' => 'secret']);
        $user->assignRole(Role::create(['name' => 'super_admin', 'guard_name' => 'web']));
        $this->actingAs($user);

        DB::table('ros_configuraciones')->insert([
            ['SedeID' => 1, 'UmbralRop' => 2000, 'created_at' => now(), 'updated_at' => now()],
            ['SedeID' => 2, 'UmbralRop' => 5000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        app(SedeContext::class)->seleccionar($user, 1);
        RosConfiguracion::resetSedeFilterCache();
        self::assertSame(2000.0, (float) RosConfiguracion::query()->sole()->UmbralRop);

        app(SedeContext::class)->seleccionar($user, 2);
        RosConfiguracion::resetSedeFilterCache();
        self::assertSame(5000.0, (float) RosConfiguracion::query()->sole()->UmbralRop);
    }

    public function test_regular_user_can_only_select_the_assigned_site(): void
    {
        $user = User::create(['name' => 'Oficial', 'username' => 'OFICIAL_SEDE', 'password' => 'secret', 'SedeID' => 2]);
        $this->actingAs($user);

        $context = app(SedeContext::class);

        self::assertSame([2], $context->sedesPermitidas($user)->pluck('SedeID')->all());
        $context->seleccionar($user, 2);
        self::assertSame(2, $context->sedeActivaId($user));
    }
}
