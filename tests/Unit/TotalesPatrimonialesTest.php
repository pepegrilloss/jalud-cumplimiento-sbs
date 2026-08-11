<?php
namespace Tests\Unit;
use App\Models\DeclaracionTrabajador;
use Tests\TestCase;
class TotalesPatrimonialesTest extends TestCase
{
    public function test_total_ingresos_usa_montos_decimales():void{$d=new DeclaracionTrabajador(['RemuneracionBrutaMensual'=>'1500.25','IngresoEjercicioIndividual'=>'250.10','OtrosIngresosMensuales'=>'99.65']);self::assertSame(1850.0,$d->TotalIngresos);}
}
