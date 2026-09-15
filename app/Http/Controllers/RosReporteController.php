<?php

namespace App\Http\Controllers;

use App\Models\RosCaso;
use App\Services\Ros\RosExcelExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RosReporteController extends Controller
{
    public function pdf(int $caso): Response
    {
        $caso = $this->caso($caso);
        $this->authorize('view', $caso);
        $caso->load(['sede', 'zona', 'cliente', 'personas.reportesPrevios', 'operaciones', 'senalesAlerta', 'tipologias', 'adjuntos', 'reportesPrevios', 'transiciones.usuario']);

        return Pdf::loadView('pdf.ros.reporte', compact('caso'))
            ->setPaper('a4')
            ->download("{$caso->CodigoInterno}.pdf");
    }

    public function excel(int $caso, RosExcelExportService $servicio): BinaryFileResponse
    {
        $caso = $this->caso($caso);
        $this->authorize('view', $caso);
        $ruta = $servicio->generar($caso);

        return response()->download($ruta, "{$caso->CodigoInterno}.xls")->deleteFileAfterSend(true);
    }

    private function caso(int $casoId): RosCaso
    {
        return RosCaso::query()->findOrFail($casoId);
    }
}
