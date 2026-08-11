<?php

namespace Tests\Feature;

use App\Models\DeclaracionCliente;
use App\Models\DeclaracionTrabajador;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\Declaraciones\GeneradorPdfDeclaraciones;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeclaracionPdfGenerationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_genera_los_tres_anexos_del_trabajador_y_el_pdf_del_cliente_en_privado(): void
    {
        Storage::fake('local');
        $user=User::query()->firstOrFail(); $this->actingAs($user); $sede=(int)($user->SedeID ?: 1);
        $t=Trabajador::create(['SedeID'=>$sede,'TipoDocumento'=>'DNI','NumeroDocumento'=>'70808080','Nombres'=>'Prueba','ApellidoPaterno'=>'PDF','Activo'=>true]);
        $d=DeclaracionTrabajador::create(['TrabajadorID'=>$t->getKey(),'SedeID'=>$sede,'NumeroVersion'=>1,'Estado'=>'FINALIZADO','FechaContratacion'=>'2026-08-01','Nombres'=>'Prueba','ApellidoPaterno'=>'PDF','TipoDocumento'=>'DNI','NumeroDocumento'=>'70808080','Nacionalidad'=>'Peruana','EstadoCivil'=>'SOLTERO','CodigoOcupacion'=>'003','Cargo'=>'Analista','DomicilioVia'=>'Av. Prueba','DomicilioDistrito'=>'Lima','DomicilioProvincia'=>'Lima','DomicilioDepartamento'=>'Lima','NumeroDependientes'=>0,'CondicionVivienda'=>'ALQUILADA','RemuneracionBrutaMensual'=>1500,'IngresoEjercicioIndividual'=>0,'OtrosIngresosMensuales'=>0,'CiudadSuscripcion'=>'Lima','FechaDeclaracion'=>'2026-08-08','CreadoPorID'=>$user->getKey(),'FinalizadoPorID'=>$user->getKey(),'FinalizadoEn'=>now()]);
        $docs=app(GeneradorPdfDeclaraciones::class)->generarTrabajador($d);
        self::assertCount(3,$docs); foreach($docs as $doc){Storage::disk('local')->assertExists($doc->RutaArchivo);self::assertStringStartsWith('%PDF',Storage::disk('local')->get($doc->RutaArchivo));}

        $c=DeclaracionCliente::create(['ClienteID'=>1,'SedeID'=>$sede,'NumeroVersion'=>1,'Estado'=>'FINALIZADO','Nombres'=>'Cliente','Apellidos'=>'Prueba','TipoDocumento'=>'DNI','NumeroDocumento'=>'10101010','Nacionalidad'=>'Peruana','EstadoCivil'=>'SOLTERO','DomicilioVia'=>'Calle Prueba','DomicilioDistrito'=>'Lima','DomicilioProvincia'=>'Lima','DomicilioDepartamento'=>'Lima','Ocupacion'=>'Comerciante','PropositoRelacion'=>'Crédito','EstadoPep'=>'NO_SOY','EstadoColaboradorAutoridad'=>'NO_SOY','EsParientePep'=>false,'TipoBeneficiario'=>'YO_MISMO','OrigenFondos'=>'Ahorros','FechaDeclaracion'=>'2026-08-08','CreadoPorID'=>$user->getKey(),'FinalizadoPorID'=>$user->getKey(),'FinalizadoEn'=>now()]);
        $docCliente=app(GeneradorPdfDeclaraciones::class)->generarCliente($c); Storage::disk('local')->assertExists($docCliente->RutaArchivo); self::assertStringStartsWith('%PDF',Storage::disk('local')->get($docCliente->RutaArchivo));
    }
}
