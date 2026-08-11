<?php

namespace Tests\Unit;

use App\Models\ExperienciaLaboral;
use App\Services\Declaraciones\DeclaracionTrabajadorService;
use Carbon\Carbon;
use Tests\TestCase;

class ExperienciasUltimosDosAniosTest extends TestCase
{
    public function test_incluye_experiencias_que_se_superponen_con_la_ventana_y_las_ordena(): void
    {
        $experiencias = collect([
            new ExperienciaLaboral(['EmpresaEntidad'=>'Antigua','FechaInicio'=>'2020-01-01','FechaConclusion'=>'2023-07-31']),
            new ExperienciaLaboral(['EmpresaEntidad'=>'Cruza límite','FechaInicio'=>'2023-01-01','FechaConclusion'=>'2024-01-01']),
            new ExperienciaLaboral(['EmpresaEntidad'=>'Reciente','FechaInicio'=>'2025-04-01','FechaConclusion'=>null]),
            new ExperienciaLaboral(['EmpresaEntidad'=>'Posterior','FechaInicio'=>'2026-09-01','FechaConclusion'=>null]),
        ]);
        $resultado = DeclaracionTrabajadorService::experienciasUltimosDosAnios($experiencias, Carbon::parse('2026-08-01'));
        self::assertSame(['Reciente'], $resultado->pluck('EmpresaEntidad')->all());
    }

    public function test_incluye_una_experiencia_que_empieza_antes_pero_termina_dentro_del_periodo(): void
    {
        $e = new ExperienciaLaboral(['EmpresaEntidad'=>'Solapada','FechaInicio'=>'2022-01-01','FechaConclusion'=>'2025-01-01']);
        self::assertCount(1, DeclaracionTrabajadorService::experienciasUltimosDosAnios(collect([$e]), Carbon::parse('2026-08-01')));
    }
}
