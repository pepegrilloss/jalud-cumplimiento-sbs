<?php

namespace App\Http\Controllers;

use App\Services\Cumplimiento\RopRosZipExportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteCumplimientoController extends Controller
{
    public function ropRos(Request $request, RopRosZipExportService $exportador): BinaryFileResponse
    {
        Gate::authorize('descargar_reportes_rop_ros');

        $datos = $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);
        $desde = CarbonImmutable::parse($datos['desde']);
        $hasta = CarbonImmutable::parse($datos['hasta']);
        $ruta = $exportador->generar($desde, $hasta);
        $nombre = "reportes-ROP-ROS-{$desde->format('Ymd')}-{$hasta->format('Ymd')}.zip";

        return response()->download($ruta, $nombre)->deleteFileAfterSend(true);
    }
}
