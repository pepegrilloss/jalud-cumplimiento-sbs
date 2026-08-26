<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalizarDniOcrRequest;
use App\Models\EnlaceCliente;
use App\Services\DniOcrService;
use Illuminate\Http\JsonResponse;

class OcrDniPublicoController extends Controller
{
    public function __invoke(AnalizarDniOcrRequest $request, string $token, DniOcrService $ocr): JsonResponse
    {
        abort_unless(strlen($token) === 64, 404);

        $enlace = EnlaceCliente::buscarPorToken($token) ?? abort(404);
        abort_if($enlace->CERRADO_AT, 422, 'Esta visita ya fue cerrada.');

        return response()->json($ocr->analizar($request->file('dni_foto')));
    }
}
