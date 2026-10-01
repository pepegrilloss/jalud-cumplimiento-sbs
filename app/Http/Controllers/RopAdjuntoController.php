<?php

namespace App\Http\Controllers;

use App\Models\RopAdjunto;
use App\Models\RopRegistro;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class RopAdjuntoController extends Controller
{
    public function ver(int $adjunto): BinaryFileResponse
    {
        return $this->responder($this->adjunto($adjunto), false);
    }

    public function descargar(int $adjunto): BinaryFileResponse
    {
        return $this->responder($this->adjunto($adjunto), true);
    }

    private function responder(RopAdjunto $adjunto, bool $descargar): BinaryFileResponse
    {
        $registro = RopRegistro::query()->findOrFail($adjunto->RopRegistroID);
        $this->authorize('view', $registro);
        abort_unless(Storage::disk('local')->exists($adjunto->RutaArchivo), 404);

        $ruta = Storage::disk('local')->path($adjunto->RutaArchivo);
        $nombre = $adjunto->NombreOriginal ?: basename($ruta);

        if ($descargar) {
            return response()->download($ruta, $nombre);
        }

        $respuesta = response()->file($ruta);
        $respuesta->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $nombre);

        return $respuesta;
    }

    private function adjunto(int $adjuntoId): RopAdjunto
    {
        return RopAdjunto::query()->findOrFail($adjuntoId);
    }
}
