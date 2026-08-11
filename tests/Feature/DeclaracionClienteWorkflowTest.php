<?php
namespace Tests\Feature;
use App\Models\DeclaracionCliente;
use App\Services\Declaraciones\DeclaracionClienteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class DeclaracionClienteWorkflowTest extends TestCase
{
 use DatabaseTransactions;
 public function test_exige_cargo_e_institucion_cuando_el_cliente_es_pep():void{$d=DeclaracionCliente::create(array_replace($this->datos(),['EstadoPep'=>'SI_SOY']));$this->expectException(ValidationException::class);app(DeclaracionClienteService::class)->finalizar($d);}
 public function test_finaliza_cliente_no_pep_con_operacion_propia():void{$d=DeclaracionCliente::create($this->datos());app(DeclaracionClienteService::class)->finalizar($d);self::assertSame('FINALIZADO',$d->fresh()->Estado);}
 private function datos():array{return['ClienteID'=>1,'SedeID'=>1,'NumeroVersion'=>1,'Estado'=>'BORRADOR','Nombres'=>'Ana','Apellidos'=>'Torres','TipoDocumento'=>'DNI','NumeroDocumento'=>'87654321','EstadoCivil'=>'SOLTERO','DomicilioVia'=>'Calle Uno','DomicilioDistrito'=>'Lima','DomicilioProvincia'=>'Lima','DomicilioDepartamento'=>'Lima','Ocupacion'=>'Comerciante','PropositoRelacion'=>'Solicitar préstamo','EstadoPep'=>'NO_SOY','EstadoColaboradorAutoridad'=>'NO_SOY','EsParientePep'=>false,'TipoBeneficiario'=>'YO_MISMO','OrigenFondos'=>'Ahorros','FechaDeclaracion'=>'2026-08-08','CreadoPorID'=>1];}
}
