<?php

namespace App\Services\Declaraciones;

use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Enums\Declaraciones\TipoDocumentoGenerado;
use App\Models\DeclaracionCliente;
use App\Models\DeclaracionDocumento;
use App\Models\DeclaracionTrabajador;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeneradorPdfDeclaraciones
{
    public function generarTrabajador(DeclaracionTrabajador $declaracion): Collection
    {
        abort_if($declaracion->Estado !== EstadoDeclaracion::FINALIZADO->value, 422, 'Finalice la declaración antes de generar documentos.');
        $declaracion->load(DeclaracionTrabajadorService::RELACIONES);
        $experienciasAnexo1 = DeclaracionTrabajadorService::experienciasUltimosDosAnios($declaracion->experienciasLaborales, $declaracion->FechaContratacion);
        return collect([
            TipoDocumentoGenerado::ANEXO_1->value => $this->guardarTrabajador($declaracion, TipoDocumentoGenerado::ANEXO_1, 'pdf.declaraciones.trabajadores.anexo-1', compact('declaracion', 'experienciasAnexo1')),
            TipoDocumentoGenerado::ANEXO_2->value => $this->guardarTrabajador($declaracion, TipoDocumentoGenerado::ANEXO_2, 'pdf.declaraciones.trabajadores.anexo-2', compact('declaracion')),
            TipoDocumentoGenerado::ANEXO_3->value => $this->guardarTrabajador($declaracion, TipoDocumentoGenerado::ANEXO_3, 'pdf.declaraciones.trabajadores.anexo-3', compact('declaracion')),
        ]);
    }

    public function generarCliente(DeclaracionCliente $declaracion): DeclaracionDocumento
    {
        abort_if($declaracion->Estado !== EstadoDeclaracion::FINALIZADO->value, 422, 'Finalice la declaración antes de generar el documento.');
        $declaracion->load(DeclaracionClienteService::RELACIONES);
        $tipo = TipoDocumentoGenerado::CLIENTE_REGIMEN_GENERAL;
        $contenido = Pdf::loadView('pdf.declaraciones.clientes.regimen-general', compact('declaracion'))->setPaper('a4')->output();
        return $this->guardar($contenido, "declaraciones/clientes/{$declaracion->DeclaracionClienteID}", $tipo, null, $declaracion);
    }

    private function guardarTrabajador(DeclaracionTrabajador $d, TipoDocumentoGenerado $tipo, string $vista, array $datos): DeclaracionDocumento
    {
        return $this->guardar(Pdf::loadView($vista, $datos)->setPaper('a4')->output(), "declaraciones/trabajadores/{$d->DeclaracionTrabajadorID}", $tipo, $d, null);
    }

    private function guardar(string $contenido, string $directorio, TipoDocumentoGenerado $tipo, ?DeclaracionTrabajador $trabajador, ?DeclaracionCliente $cliente): DeclaracionDocumento
    {
        $nombreInterno = Str::ulid().'.pdf';
        $ruta = "{$directorio}/{$nombreInterno}";
        abort_unless(Storage::disk('local')->put($ruta, $contenido), 500, 'No se pudo almacenar el PDF.');
        return DeclaracionDocumento::create([
            'DeclaracionTrabajadorID' => $trabajador?->getKey(), 'DeclaracionClienteID' => $cliente?->getKey(),
            'SedeID' => $trabajador?->SedeID ?? $cliente?->SedeID, 'TipoDocumento' => $tipo->value,
            'RutaArchivo' => $ruta, 'NombreArchivo' => $tipo->nombreArchivo(), 'HashSha256' => hash('sha256', $contenido),
            'TamanioBytes' => strlen($contenido), 'GeneradoPorID' => auth()->id(), 'GeneradoEn' => now(),
        ]);
    }
}
