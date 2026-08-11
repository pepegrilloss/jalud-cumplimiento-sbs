<?php

namespace Tests\Feature;

use App\Models\DeclaracionTrabajador;
use App\Models\ExperienciaLaboral;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\Declaraciones\DeclaracionTrabajadorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeclaracionTrabajadorWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_finaliza_con_datos_validos_y_bloquea_cambios_posteriores(): void
    {
        $d=$this->declaracion(); app(DeclaracionTrabajadorService::class)->finalizar($d);
        self::assertSame('FINALIZADO',$d->fresh()->Estado);
        $this->expectException(ValidationException::class); $d->fresh()->update(['Cargo'=>'Alterado']);
    }

    public function test_crea_nueva_version_con_la_experiencia_clonada(): void
    {
        $this->actingAs(User::role('super_admin')->firstOrFail());
        $d=$this->declaracion(); ExperienciaLaboral::create(['DeclaracionTrabajadorID'=>$d->getKey(),'EmpresaEntidad'=>'Empresa','CargoDesempenado'=>'Analista','FechaInicio'=>'2025-01-01','Orden'=>1]);
        app(DeclaracionTrabajadorService::class)->finalizar($d); $nueva=app(DeclaracionTrabajadorService::class)->nuevaVersion($d->fresh());
        self::assertSame(2,$nueva->NumeroVersion); self::assertSame('BORRADOR',$nueva->Estado); self::assertCount(1,$nueva->experienciasLaborales);
        ExperienciaLaboral::create(['DeclaracionTrabajadorID'=>$nueva->getKey(),'EmpresaEntidad'=>'Empresa 2','CargoDesempenado'=>'Jefe','FechaInicio'=>'2026-01-01','Orden'=>2]);
        app(DeclaracionTrabajadorService::class)->finalizar($nueva); $tercera=app(DeclaracionTrabajadorService::class)->nuevaVersion($d->fresh());
        self::assertSame(3,$tercera->NumeroVersion); self::assertCount(2,$tercera->experienciasLaborales);
    }

    private function declaracion(): DeclaracionTrabajador
    {
        $t=Trabajador::create(['SedeID'=>1,'TipoDocumento'=>'DNI','NumeroDocumento'=>'12345678','Nombres'=>'Juan','ApellidoPaterno'=>'Pérez','Activo'=>true]);
        return DeclaracionTrabajador::create(['TrabajadorID'=>$t->getKey(),'SedeID'=>1,'NumeroVersion'=>1,'Estado'=>'BORRADOR','FechaContratacion'=>'2026-08-01','Nombres'=>'Juan','ApellidoPaterno'=>'Pérez','TipoDocumento'=>'DNI','NumeroDocumento'=>'12345678','EstadoCivil'=>'SOLTERO','CodigoOcupacion'=>'003','Cargo'=>'Analista','DomicilioVia'=>'Av. Lima','DomicilioDistrito'=>'Lima','DomicilioProvincia'=>'Lima','DomicilioDepartamento'=>'Lima','NumeroDependientes'=>0,'RemuneracionBrutaMensual'=>0,'IngresoEjercicioIndividual'=>0,'OtrosIngresosMensuales'=>0,'CiudadSuscripcion'=>'Lima','FechaDeclaracion'=>'2026-08-08','CreadoPorID'=>1]);
    }
}
