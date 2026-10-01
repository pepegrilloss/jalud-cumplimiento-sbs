<?php

namespace App\Http\Controllers;

use App\Models\RosAdjunto;
use App\Models\RosCaso;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class RosAdjuntoController extends Controller
{
    public function ver(int $adjunto): BinaryFileResponse
    {
        return $this->responder($this->adjunto($adjunto), false);
    }

    public function descargar(int $adjunto): BinaryFileResponse
    {
        return $this->responder($this->adjunto($adjunto), true);
    }

    private function responder(RosAdjunto $adjunto, bool $descargar): BinaryFileResponse
    {
        $this->authorize('view', RosCaso::query()->findOrFail($adjunto->RosCasoID));
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

    private function adjunto(int $adjuntoId): RosAdjunto
    {
        return RosAdjunto::query()->findOrFail($adjuntoId);
    }
}
