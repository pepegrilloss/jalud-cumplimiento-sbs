<?php

namespace App\Http\Controllers;

use App\Models\DeclaracionCliente;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeclaracionClienteDocumentoController extends Controller
{
    public function ver(int $declaracion): BinaryFileResponse { return $this->respuesta($declaracion,false); }
    public function descargar(int $declaracion): BinaryFileResponse { return $this->respuesta($declaracion,true); }
    private function respuesta(int $declaracionId,bool $descargar):BinaryFileResponse{$declaracion=DeclaracionCliente::query()->findOrFail($declaracionId);$this->authorize('descargar',$declaracion);$doc=$declaracion->documentos()->where('TipoDocumento','CLIENTE_REGIMEN_GENERAL')->latest('GeneradoEn')->firstOrFail();$ruta=Storage::disk('local')->path($doc->RutaArchivo);return$descargar?response()->download($ruta,$doc->NombreArchivo):response()->file($ruta,['Content-Disposition'=>'inline; filename="'.$doc->NombreArchivo.'"']);}
}
