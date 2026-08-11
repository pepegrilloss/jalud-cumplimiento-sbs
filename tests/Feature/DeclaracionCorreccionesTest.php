<?php

namespace Tests\Feature;

use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\Pages\CreateDeclaracionTrabajador;
use App\Models\DeclaracionCliente;
use App\Models\DeclaracionTrabajador;
use App\Models\Estudio;
use App\Models\ExperienciaLaboral;
use App\Models\LogAuditoria;
use App\Models\PepFamiliar;
use App\Models\PepRelacionado;
use App\Models\Trabajador;
use App\Models\User;
use App\Policies\DeclaracionTrabajadorPolicy;
use App\Services\Declaraciones\DeclaracionClienteService;
use App\Services\Declaraciones\DeclaracionTrabajadorService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class DeclaracionCorreccionesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_no_pep_limpia_cargo_institucion_y_familiares(): void
    {
        $d=DeclaracionCliente::create($this->cliente(['PepCargo'=>'Alcalde','PepInstitucion'=>'Municipalidad']));
        PepFamiliar::create(['DeclaracionClienteID'=>$d->getKey(),'Nombres'=>'Familiar','Apellidos'=>'PEP','Parentesco'=>'Padre','Orden'=>1]);
        PepRelacionado::create(['DeclaracionClienteID'=>$d->getKey(),'Nombres'=>'Relacionado','Apellidos'=>'PEP','Parentesco'=>'Padre','Orden'=>1]);
        app(DeclaracionClienteService::class)->finalizar($d);
        self::assertNull($d->fresh()->PepCargo); self::assertNull($d->fresh()->PepInstitucion); self::assertCount(0,$d->fresh()->pepFamiliares); self::assertCount(0,$d->fresh()->pepRelacionados);
    }

    public function test_beneficiario_propio_descarta_datos_anteriores_del_tercero(): void
    {
        $d=DeclaracionCliente::create($this->cliente(['TerceroNombresApellidos'=>'Juan Pérez','TerceroTipoDocumento'=>'DNI','TerceroNumeroDocumento'=>'12345678','TerceroRepresentacion'=>'ESCRITURA_PUBLICA','TerceroEstadoPep'=>'SI_HA_SIDO','TerceroPepCargo'=>'Cargo']));
        self::assertNull($d->TerceroNombresApellidos); self::assertNull($d->TerceroRepresentacion); self::assertStringNotContainsString('PODER_ACTA',view('pdf.declaraciones.clientes.regimen-general',['declaracion'=>$d->setRelation('pepFamiliares',collect())->setRelation('pepRelacionados',collect())])->render());
    }

    public function test_antecedente_negativo_elimina_la_razon(): void
    {
        $d=DeclaracionTrabajador::create($this->trabajador(['TieneAntecedentesPoliciales'=>false,'DetalleAntecedentesPoliciales'=>'Dato sensible']));
        self::assertNull($d->DetalleAntecedentesPoliciales);
    }

    public function test_documento_otro_exige_descripcion_y_dni_la_descarta(): void
    {
        $otro=DeclaracionTrabajador::create($this->trabajador(['TipoDocumento'=>'OTRO','NumeroDocumento'=>'ABC-123']));
        try { app(DeclaracionTrabajadorService::class)->finalizar($otro); self::fail('Debió exigir TipoDocumentoOtro'); } catch (ValidationException $e) { self::assertArrayHasKey('TipoDocumentoOtro',$e->errors()); }
        $dni=DeclaracionTrabajador::create($this->trabajador(['NumeroDocumento'=>'22345678','TipoDocumentoOtro'=>'Licencia']));
        self::assertNull($dni->TipoDocumentoOtro);
        $cliente=DeclaracionCliente::create($this->cliente(['TipoDocumento'=>'OTRO','NumeroDocumento'=>'EXT-123']));
        try { app(DeclaracionClienteService::class)->finalizar($cliente); self::fail('Debió exigir TipoDocumentoOtro del cliente'); } catch (ValidationException $e) { self::assertArrayHasKey('TipoDocumentoOtro',$e->errors()); }
    }

    public function test_detalle_de_declaracion_finalizada_es_inmutable(): void
    {
        $d=DeclaracionTrabajador::create($this->trabajador()); app(DeclaracionTrabajadorService::class)->finalizar($d);
        $this->expectException(ValidationException::class);
        ExperienciaLaboral::create(['DeclaracionTrabajadorID'=>$d->getKey(),'EmpresaEntidad'=>'Empresa','CargoDesempenado'=>'Cargo','FechaInicio'=>'2025-01-01','Orden'=>1]);
    }

    public function test_usuario_sin_permiso_no_puede_versionar(): void
    {
        $u=Mockery::mock(User::class)->makePartial(); $u->shouldReceive('hasRole')->with('super_admin')->andReturnFalse(); $u->shouldReceive('can')->with('versionar_declaraciones_trabajador')->andReturnFalse();
        self::assertFalse((new DeclaracionTrabajadorPolicy)->nuevaVersion($u,new DeclaracionTrabajador(['Estado'=>'FINALIZADO'])));
    }

    public function test_servicio_rechaza_versionar_sin_permiso(): void
    {
        $user=User::create(['name'=>'Sin versión','email'=>'sin-version-'.uniqid().'@test.local','username'=>'sin-version-'.uniqid(),'password'=>'secret','SedeID'=>1]);
        $this->actingAs($user); $d=DeclaracionTrabajador::create($this->trabajador(['Estado'=>'FINALIZADO']));
        $this->expectException(AuthorizationException::class); app(DeclaracionTrabajadorService::class)->nuevaVersion($d);
    }

    public function test_nueva_ficha_no_crea_version_vacia_si_ya_existe_una_declaracion(): void
    {
        $d=DeclaracionTrabajador::create($this->trabajador());
        $this->expectException(ValidationException::class); app(DeclaracionTrabajadorService::class)->datosNuevaFicha($d->trabajador);
    }

    public function test_usuario_de_otra_sede_no_descarga_por_request_directo(): void
    {
        $user=User::create(['name'=>'Sede uno','email'=>'sede-'.uniqid().'@test.local','username'=>'sede-'.uniqid(),'password'=>'secret','SedeID'=>1]);
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('descargar_declaraciones','web'));
        $this->actingAs($user);
        $d=DeclaracionCliente::create($this->cliente(['SedeID'=>2]));
        $this->get(route('declaraciones.clientes.documento.descargar',$d->getKey()))->assertNotFound();
    }

    public function test_auditoria_registra_campos_pero_no_valores_sensibles(): void
    {
        $this->actingAs(User::role('super_admin')->firstOrFail());
        $d=DeclaracionTrabajador::create($this->trabajador(['DetalleAntecedentesPoliciales'=>'SECRETO AUDITORIA','TieneAntecedentesPoliciales'=>true]));
        $log=LogAuditoria::where('modelo','DeclaracionTrabajador')->where('modelo_id',$d->getKey())->latest('id')->firstOrFail();
        self::assertStringNotContainsString('SECRETO AUDITORIA',json_encode([$log->old_values,$log->new_values]));
        self::assertContains('DetalleAntecedentesPoliciales',$log->new_values['campos_modificados']);
    }

    public function test_pdf_presenta_etiquetas_humanas_sin_codigos_internos(): void
    {
        $d=DeclaracionTrabajador::create($this->trabajador());
        Estudio::create(['DeclaracionTrabajadorID'=>$d->getKey(),'Nivel'=>'MAESTRIA','EstadoEstudio'=>'EN_TRAMITE','TituloGrado'=>'Gestión','Orden'=>1]);
        $d->load(['estudios','educacionEscolar','otrosEstudios','experienciasLaborales','capacitaciones']);
        $html=view('pdf.declaraciones.trabajadores.anexo-2',['declaracion'=>$d])->render();
        self::assertStringContainsString('Maestría',$html); self::assertStringContainsString('En trámite',$html); self::assertStringNotContainsString('EN_TRAMITE',$html);

        $cliente=DeclaracionCliente::create($this->cliente(['TipoBeneficiario'=>'TERCERO_NATURAL','TerceroNombresApellidos'=>'Luis Pérez','TerceroTipoDocumento'=>'DNI','TerceroNumeroDocumento'=>'12345678','TerceroRepresentacion'=>'ESCRITURA_PUBLICA','TerceroEstadoPep'=>'SI_HA_SIDO','TerceroPepCargo'=>'Director','TerceroPepInstitucion'=>'Entidad']));
        $cliente->setRelation('pepFamiliares',collect())->setRelation('pepRelacionados',collect());
        $htmlCliente=view('pdf.declaraciones.clientes.regimen-general',['declaracion'=>$cliente])->render();
        foreach(['CARNET_EXTRANJERIA','ESCRITURA_PUBLICA','PODER_ACTA','SI_HA_SIDO'] as $codigo){self::assertStringNotContainsString($codigo,$htmlCliente);}
        self::assertStringContainsString('Poder por Escritura Pública ( X )',$htmlCliente);
        self::assertStringContainsString('SI HA SIDO ( X )',$htmlCliente);
    }

    public function test_anexo_dos_usa_historial_completo_y_anexo_uno_solo_ventana_de_dos_anios(): void
    {
        $d=DeclaracionTrabajador::create($this->trabajador());
        foreach(range(1,10) as $i){ExperienciaLaboral::create(['DeclaracionTrabajadorID'=>$d->getKey(),'EmpresaEntidad'=>'Empresa '.$i,'CargoDesempenado'=>'Cargo '.$i,'FechaInicio'=>(2015+$i).'-01-01','FechaConclusion'=>(2015+$i).'-12-01','Orden'=>$i]);}
        $d->load(['estudios','educacionEscolar','otrosEstudios','experienciasLaborales','capacitaciones']);
        $html=view('pdf.declaraciones.trabajadores.anexo-2',['declaracion'=>$d])->render(); self::assertStringContainsString('Empresa 1',$html); self::assertStringContainsString('Empresa 10',$html);
        $ventana=DeclaracionTrabajadorService::experienciasUltimosDosAnios($d->experienciasLaborales,$d->FechaContratacion); self::assertLessThan(10,$ventana->count()); self::assertFalse($ventana->contains('EmpresaEntidad','Empresa 1'));
    }

    protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

    private function nuevoTrabajador(string $dni='12345678'): Trabajador { return Trabajador::create(['SedeID'=>1,'TipoDocumento'=>'DNI','NumeroDocumento'=>$dni,'Nombres'=>'Juan','ApellidoPaterno'=>'Pérez','Activo'=>true]); }
    private function trabajador(array $cambios=[]): array
    {
        $t=$this->nuevoTrabajador($cambios['NumeroDocumento']??'12345678');
        return array_replace(['TrabajadorID'=>$t->getKey(),'SedeID'=>1,'NumeroVersion'=>1,'Estado'=>'BORRADOR','FechaContratacion'=>'2026-08-01','Nombres'=>'Juan','ApellidoPaterno'=>'Pérez','TipoDocumento'=>'DNI','NumeroDocumento'=>'12345678','EstadoCivil'=>'SOLTERO','CodigoOcupacion'=>'003','Cargo'=>'Analista','DomicilioVia'=>'Av. Lima','DomicilioDistrito'=>'Lima','DomicilioProvincia'=>'Lima','DomicilioDepartamento'=>'Lima','NumeroDependientes'=>0,'RemuneracionBrutaMensual'=>0,'IngresoEjercicioIndividual'=>0,'OtrosIngresosMensuales'=>0,'CiudadSuscripcion'=>'Lima','FechaDeclaracion'=>'2026-08-08','CreadoPorID'=>1],$cambios);
    }
    private function cliente(array $cambios=[]): array
    {
        return array_replace(['ClienteID'=>1,'SedeID'=>1,'NumeroVersion'=>random_int(20,1000000),'Estado'=>'BORRADOR','Nombres'=>'Ana','Apellidos'=>'Torres','TipoDocumento'=>'DNI','NumeroDocumento'=>'87654321','EstadoCivil'=>'SOLTERO','DomicilioVia'=>'Calle Uno','DomicilioDistrito'=>'Lima','DomicilioProvincia'=>'Lima','DomicilioDepartamento'=>'Lima','Ocupacion'=>'Comerciante','PropositoRelacion'=>'Crédito','EstadoPep'=>'NO_SOY','EstadoColaboradorAutoridad'=>'NO_SOY','EsParientePep'=>false,'TipoBeneficiario'=>'YO_MISMO','OrigenFondos'=>'Ahorros','FechaDeclaracion'=>'2026-08-08','CreadoPorID'=>1],$cambios);
    }
}
