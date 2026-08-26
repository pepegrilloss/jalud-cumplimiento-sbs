<?php

namespace Tests\Unit;

use App\Models\EnlaceCliente;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EnlaceClienteTest extends TestCase
{
    public function test_genera_una_llave_aleatoria_de_64_caracteres(): void
    {
        $token = EnlaceCliente::tokenNuevo();

        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{64}$/', $token);
    }

    public function test_guarda_hash_y_copia_cifrada_sin_exponer_la_llave(): void
    {
        $token = EnlaceCliente::tokenNuevo();
        $atributos = EnlaceCliente::atributosToken($token);

        $this->assertSame(hash('sha256', $token), $atributos['TOKEN_HASH']);
        $this->assertNotSame($token, $atributos['TOKEN_CIFRADO']);
        $this->assertSame($token, Crypt::decryptString($atributos['TOKEN_CIFRADO']));
    }

    public function test_puede_generar_el_codigo_qr_localmente(): void
    {
        $qr = new QrCode(
            data: 'https://visitas.medlogpiura.pe/registro/'.EnlaceCliente::tokenNuevo(),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $svg = (new SvgWriter)->write($qr)->getString();

        $this->assertStringContainsString('<svg', $svg);
    }
}
