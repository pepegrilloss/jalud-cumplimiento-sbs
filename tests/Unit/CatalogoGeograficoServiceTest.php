<?php

namespace Tests\Unit;

use App\Services\Geografia\CatalogoGeograficoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogoGeograficoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_devuelve_paises_en_espanol_ordenados_para_los_selectores(): void
    {
        config()->set('services.geografia.paises_url', 'https://catalogo.test/paises');

        Http::fake([
            'https://catalogo.test/paises' => Http::response([
                'PE' => 'Perú',
                'AR' => 'Argentina',
            ]),
        ]);

        $paises = app(CatalogoGeograficoService::class)->paises();

        $this->assertSame([
            'Argentina' => 'Argentina',
            'Perú' => 'Perú',
        ], $paises);
    }

    public function test_encadena_departamentos_provincias_y_distritos_del_catalogo_ubigeo(): void
    {
        config()->set('services.geografia.ubigeos_url', 'https://catalogo.test/ubigeos');

        Http::fake([
            'https://catalogo.test/ubigeos' => Http::response([
                'LIMA' => [
                    'LIMA' => [
                        'MIRAFLORES' => ['ubigeo' => '150122'],
                        'SAN ISIDRO' => ['ubigeo' => '150131'],
                    ],
                    'CAÑETE' => [
                        'ASIA' => ['ubigeo' => '150502'],
                    ],
                ],
                'CUSCO' => [
                    'CUSCO' => [
                        'CUSCO' => ['ubigeo' => '080101'],
                    ],
                ],
            ]),
        ]);

        $catalogo = app(CatalogoGeograficoService::class);

        $this->assertSame(['Cusco' => 'Cusco', 'Lima' => 'Lima'], $catalogo->departamentos());
        $this->assertSame(['Cañete' => 'Cañete', 'Lima' => 'Lima'], $catalogo->provincias('lima'));
        $this->assertSame(['Miraflores' => 'Miraflores', 'San Isidro' => 'San Isidro'], $catalogo->distritos('LIMA', 'lima'));
    }

    public function test_no_cachea_un_catalogo_vacio_y_reintenta_despues_de_una_caida(): void
    {
        config()->set('services.geografia.paises_url', 'https://catalogo.test/paises');

        Http::fakeSequence('https://catalogo.test/paises')
            ->push([], 503)
            ->push(['PE' => 'Perú']);

        $catalogo = app(CatalogoGeograficoService::class);

        $this->assertSame([], $catalogo->paises());
        $this->assertSame(['Perú' => 'Perú'], $catalogo->paises());
    }
}
