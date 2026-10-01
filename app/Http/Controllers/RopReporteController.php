<?php

namespace App\Http\Controllers;

use App\Models\RopRegistro;
use App\Services\Cumplimiento\PlantillaExcelPdfService;
use App\Services\Rop\RopCompletenessService;
use App\Services\Rop\RopExcelExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RopReporteController extends Controller
{
    public function pdf(
        int $registro,
        RopExcelExportService $exportador,
        PlantillaExcelPdfService $pdfExportador,
        RopCompletenessService $completeness,
    ): BinaryFileResponse {
        $rop = RopRegistro::query()->findOrFail($registro);
        $this->authorize('view', $rop);
        abort_unless($completeness->estaCompleto($rop), 404);
        $ruta = $pdfExportador->generar($exportador->libro($rop), $rop->NumeroRop);

        return response()->file($ruta, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$rop->NumeroRop.'.pdf"',
        ])->deleteFileAfterSend(true);
    }

    public function excel(
        int $registro,
        RopExcelExportService $exportador,
        RopCompletenessService $completeness,
    ): BinaryFileResponse {
        $rop = RopRegistro::query()->findOrFail($registro);
        $this->authorize('view', $rop);
        $completeness->validar($rop);
        $ruta = $exportador->generar($rop);

        return response()->download($ruta, "{$rop->NumeroRop}.xlsx")->deleteFileAfterSend(true);
    }
}
