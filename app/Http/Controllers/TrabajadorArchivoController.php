<?php

namespace App\Http\Controllers;

use App\Models\TrabajadorArchivo;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrabajadorArchivoController extends Controller
{
    public function ver(TrabajadorArchivo $archivo): StreamedResponse
    {
        Gate::authorize('view', $archivo);

        abort_unless(Storage::disk('local')->exists($archivo->RutaArchivo), 404);

        return Storage::disk('local')->response(
            $archivo->RutaArchivo,
            $archivo->NombreOriginal ?: basename($archivo->RutaArchivo),
        );
    }

    public function descargar(TrabajadorArchivo $archivo): StreamedResponse
    {
        Gate::authorize('view', $archivo);

        abort_unless(Storage::disk('local')->exists($archivo->RutaArchivo), 404);

        return Storage::disk('local')->download(
            $archivo->RutaArchivo,
            $archivo->NombreOriginal ?: basename($archivo->RutaArchivo),
        );
    }
}
