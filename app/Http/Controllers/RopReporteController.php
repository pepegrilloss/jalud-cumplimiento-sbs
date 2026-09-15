<?php

namespace App\Http\Controllers;

use App\Models\RopRegistro;
use App\Services\Rop\RopCompletenessService;
use App\Services\Rop\RopExcelExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RopReporteController extends Controller
{
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
