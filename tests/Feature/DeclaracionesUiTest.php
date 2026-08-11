<?php

namespace Tests\Feature;

use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeclaracionesUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_super_admin_puede_renderizar_listados_y_wizards(): void
    {
        $user=User::role('super_admin')->firstOrFail();
        $this->actingAs($user);
        $this->get('/cumplimiento/declaraciones-trabajadores')->assertOk()->assertSee('Declaraciones De Trabajadores');
        $this->get('/cumplimiento/declaraciones-trabajadores/create')->assertOk()->assertSee('Datos personales');
        $this->get('/cumplimiento/declaraciones-clientes')->assertOk()->assertSee('Declaraciones De Clientes');
        $this->get('/cumplimiento/declaraciones-clientes/create')->assertOk()->assertSee('Datos generales')->assertSee('Busque por DNI o nombres y apellidos');

        self::assertSame(
            Cliente::withoutGlobalScope('sede')->where('Activo',true)->count(),
            DeclaracionClienteResource::clientesDisponiblesQuery()->count(),
        );
    }
}
