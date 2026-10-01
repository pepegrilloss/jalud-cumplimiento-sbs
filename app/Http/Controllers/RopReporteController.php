<?php

namespace App\Http\Controllers;

use App\Models\RopRegistro;
use App\Models\RopCatalogoItem;
use App\Services\Rop\RopCompletenessService;
use App\Services\Rop\RopExcelExportService;
use App\Services\Rop\RopOfficialDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Response;

class RopReporteController extends Controller
{
    public function pdf(int $registro, RopOfficialDataService $datosOficiales): Response
    {
        $rop = RopRegistro::query()->findOrFail($registro);
        $this->authorize('view', $rop);
        $rop->loadMissing(['adjuntos.subidoPor', 'auditorias.usuario']);
        $datos = $datosOficiales->datos($rop);
        foreach ([
            ['tipo_sujeto_obligado', RopCatalogoItem::TIPO_SUJETO_OBLIGADO],
            ['persona_juridica.actividad_economica', RopCatalogoItem::ACTIVIDAD_ECONOMICA],
        ] as [$ruta, $tipo]) {
            $codigo = data_get($datos, $ruta);

            if (filled($codigo)) {
                data_set($datos, $ruta, RopCatalogoItem::query()
                    ->where('Tipo', $tipo)
                    ->where('Codigo', $codigo)
                    ->value('Nombre') ?: $codigo);
            }
        }

        $datosRegulatorios = $this->aplanar($datos);

        $contenido = Pdf::loadView('pdf.rop.reporte', compact('rop', 'datosRegulatorios'))
            ->setPaper('a4')
            ->output();

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$rop->NumeroRop.'.pdf"',
        ]);
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

    private function aplanar(array $datos, string $prefijo = ''): array
    {
        $filas = [];

        foreach ($datos as $clave => $valor) {
            $ruta = trim($prefijo.' / '.str_replace('_', ' ', ucfirst((string) $clave)), ' /');

            if (is_array($valor)) {
                if (array_is_list($valor)) {
                    if ($valor !== []) {
                        $filas[$ruta] = implode(', ', array_map(
                            fn ($item): string => is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE),
                            $valor,
                        ));
                    }

                    continue;
                }

                $filas += $this->aplanar($valor, $ruta);

                continue;
            }

            if ($valor !== null && $valor !== '') {
                $filas[$ruta] = is_bool($valor) ? ($valor ? 'Sí' : 'No') : (string) $valor;
            }
        }

        return $filas;
    }
}
