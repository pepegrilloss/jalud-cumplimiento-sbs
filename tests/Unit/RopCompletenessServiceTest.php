<?php

namespace Tests\Unit;

use App\Models\RopRegistro;
use App\Services\Rop\RopCompletenessService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RopCompletenessServiceTest extends TestCase
{
    public function test_incomplete_rop_cannot_pass_validation(): void
    {
        $rop = $this->rop();

        self::assertFalse(app(RopCompletenessService::class)->estaCompleto($rop));

        $this->expectException(ValidationException::class);
        app(RopCompletenessService::class)->validar($rop);
    }

    public function test_completed_rop_passes_validation(): void
    {
        $rop = $this->rop();
        $rop->DatosRegulatorios = self::datosCompletos();

        self::assertTrue(app(RopCompletenessService::class)->estaCompleto($rop));
        app(RopCompletenessService::class)->validar($rop);
    }

    public static function datosCompletos(): array
    {
        return [
            'organismo_supervisor' => 'SBS/UIF-PERU',
            'codigo_sujeto_obligado' => 'EMP-1',
            'codigo_oficial_cumplimiento' => 'OC-1',
            'tipo_sujeto_obligado' => 'OTRO',
            'tipos_operacion' => ['OTRO'],
            'individual_multiple' => 'INDIVIDUAL',
            'tipo_persona' => 'NATURAL',
            'persona_natural' => [
                'apellido_paterno' => 'PEREZ',
                'apellido_materno' => 'LOPEZ',
                'nombres' => 'JUAN',
                'tipo_documento' => 'DNI',
                'numero_documento' => '12345678',
            ],
        ];
    }

    private function rop(): RopRegistro
    {
        return new RopRegistro([
            'NumeroRop' => 'ROP-000001',
            'FechaOperacion' => '2026-09-10 10:00:00',
            'ClienteNombre' => 'CLIENTE PRUEBA',
            'CodigoCredito' => 'C-1',
            'Monto' => 5000,
            'Moneda' => 'PEN',
        ]);
    }
}
