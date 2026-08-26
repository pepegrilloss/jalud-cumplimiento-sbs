<?php

namespace App\Http\Controllers;

use App\Helpers\UsuarioHelper;
use App\Models\RegistroCliente;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DniClienteController extends Controller
{
    public function __invoke(RegistroCliente $registro): BinaryFileResponse
    {
        abort_unless(UsuarioHelper::esTI() || UsuarioHelper::esComercial() || UsuarioHelper::esGarita(), 403);
        abort_unless(Storage::disk('local')->exists($registro->DNI_PATH), 404);

        $response = response()->file(Storage::disk('local')->path($registro->DNI_PATH));
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
