<?php

namespace App\Http\Controllers;

use App\Models\RosCaso;
use App\Services\Cumplimiento\PlantillaExcelPdfService;
use App\Services\Ros\RosExcelExportService;
use App\Services\Ros\RosWorkflowService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RosReporteController extends Controller
{
    public function pdf(
        int $caso,
        RosExcelExportService $exportador,
        PlantillaExcelPdfService $pdfExportador,
        RosWorkflowService $workflow,
    ): BinaryFileResponse {
        $caso = $this->caso($caso);
        $this->authorize('view', $caso);
        abort_unless($workflow->reporteCompleto($caso), 404);

        $ruta = $pdfExportador->generar($exportador->libro($caso), $caso->CodigoInterno);

        return response()->file($ruta, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$caso->CodigoInterno.'.pdf"',
        ])->deleteFileAfterSend(true);
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
