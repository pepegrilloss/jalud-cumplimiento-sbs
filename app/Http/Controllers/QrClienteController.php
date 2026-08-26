<?php

namespace App\Http\Controllers;

use App\Helpers\UsuarioHelper;
use App\Models\EnlaceCliente;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;

class QrClienteController extends Controller
{
    public function __invoke(EnlaceCliente $enlace): Response
    {
        abort_unless(UsuarioHelper::esTI() || UsuarioHelper::esComercial(), 403);

        $qrCode = new QrCode(
            data: $enlace->urlPublica(),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $result = (new SvgWriter)->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
