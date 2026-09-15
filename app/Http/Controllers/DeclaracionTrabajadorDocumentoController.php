<?php

namespace App\Http\Controllers;

use App\Models\DeclaracionTrabajador;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeclaracionTrabajadorDocumentoController extends Controller
{
    public function ver(int $declaracion, string $tipo): BinaryFileResponse { return $this->respuesta($declaracion, $tipo, false); }
    public function descargar(int $declaracion, string $tipo): BinaryFileResponse { return $this->respuesta($declaracion, $tipo, true); }

    public function todos(int $declaracion): BinaryFileResponse
    {
        $declaracion = DeclaracionTrabajador::query()->findOrFail($declaracion);
        $this->authorize('descargar', $declaracion);
        $tipos = ['ANEXO_1', 'ANEXO_2', 'ANEXO_3'];
        $zipPath = tempnam(sys_get_temp_dir(), 'dj-').'.zip';
        $zip = new \ZipArchive(); $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($tipos as $tipo) { $doc=$declaracion->documentos()->where('TipoDocumento',$tipo)->latest('GeneradoEn')->firstOrFail(); $zip->addFromString($doc->NombreArchivo, Storage::disk('local')->get($doc->RutaArchivo)); }
        $zip->close();
        return response()->download($zipPath, "Declaraciones - {$declaracion->NumeroDocumento} - v{$declaracion->NumeroVersion}.zip")->deleteFileAfterSend(true);
    }

    private function respuesta(int $declaracionId, string $tipo, bool $descargar): BinaryFileResponse
    {
        $declaracion = DeclaracionTrabajador::query()->findOrFail($declaracionId);
        $this->authorize('descargar', $declaracion);
        abort_unless(in_array($tipo, ['ANEXO_1','ANEXO_2','ANEXO_3'], true), 404);
        $doc=$declaracion->documentos()->where('TipoDocumento',$tipo)->latest('GeneradoEn')->firstOrFail();
        $ruta=Storage::disk('local')->path($doc->RutaArchivo);
        return $descargar ? response()->download($ruta,$doc->NombreArchivo) : response()->file($ruta,['Content-Disposition'=>'inline; filename="'.$doc->NombreArchivo.'"']);
    }
}
