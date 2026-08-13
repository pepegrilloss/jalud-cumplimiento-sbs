<?php

namespace Tests\Unit;

use App\Exceptions\ConsultaDniPeruException;
use App\Services\Declaraciones\ConsultaDniPeruService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConsultaDniPeruTest extends TestCase
{
    public function test_consulta_dni_y_normaliza_nombres_y_apellidos(): void
    {
        config(['services.apis_net_pe.token' => 'test-api-token']);
        Cache::forget('apis-net-pe:dni:12345678');
        Http::fake([
            '*' => Http::response([
                'dni' => '12345678',
                'nombres' => 'JUAN CARLOS',
                'apellidoPaterno' => 'PEREZ',
                'apellidoMaterno' => 'TORRES',
            ]),
        ]);

        $persona = app(ConsultaDniPeruService::class)->consultar('12345678');

        self::assertSame([
            'dni' => '12345678',
            'nombres' => 'JUAN CARLOS',
            'apellidoPaterno' => 'PEREZ',
            'apellidoMaterno' => 'TORRES',
        ], $persona);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.apis.net.pe/v1/dni?numero=12345678'
            && $request->hasHeader('Authorization', 'Bearer test-api-token')
            && $request->hasHeader('Referer', 'https://apis.net.pe/consulta-dni-api'));
    }

    public function test_rechaza_un_dni_que_no_tenga_ocho_digitos(): void
    {
        Http::fake();

        self::assertNull(app(ConsultaDniPeruService::class)->consultar('123'));
        Http::assertNothingSent();
    }

    public function test_devuelve_null_si_el_servicio_externo_no_responde(): void
    {
        config(['services.apis_net_pe.token' => 'test-api-token']);
        Cache::forget('apis-net-pe:dni:12345678');
        Http::fake(['*' => Http::response([], 503)]);

        self::assertNull(app(ConsultaDniPeruService::class)->consultar('12345678'));
    }

    public function test_reutiliza_el_resultado_en_cache_sin_consumir_otra_consulta(): void
    {
        config(['services.apis_net_pe.token' => 'test-api-token']);
        Cache::put('apis-net-pe:dni:87654321', ['dni'=>'87654321','nombres'=>'ANA','apellidoPaterno'=>'RUIZ','apellidoMaterno'=>'DIAZ'], now()->addDay());
        Http::fake();

        self::assertSame('ANA', app(ConsultaDniPeruService::class)->consultar('87654321')['nombres']);
        Http::assertNothingSent();
    }

    public function test_informa_cuando_el_token_es_rechazado(): void
    {
        config(['services.apis_net_pe.token' => 'test-api-token']);
        Cache::forget('apis-net-pe:dni:11223344');
        Http::fake(['*' => Http::response(['message'=>'Unauthenticated'], 401)]);

        $this->expectException(ConsultaDniPeruException::class);
        $this->expectExceptionMessage('token de apis.net.pe');
        app(ConsultaDniPeruService::class)->consultar('11223344');
    }
}
