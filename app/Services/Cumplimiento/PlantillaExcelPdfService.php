<?php

namespace App\Services\Cumplimiento;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;

class PlantillaExcelPdfService
{
    public function generar(Spreadsheet $libro, string $nombre): string
    {
        $directorio = 'cumplimiento-sbs/reportes-temporales';
        $disco = Storage::disk('local');
        $disco->makeDirectory($directorio);

        $nombreSeguro = Str::slug(pathinfo($nombre, PATHINFO_FILENAME)) ?: 'reporte';
        $rutaRelativa = "{$directorio}/{$nombreSeguro}-".Str::uuid().'.pdf';
        $rutaAbsoluta = $disco->path($rutaRelativa);

        try {
            $writer = new Dompdf($libro);
            $escala = $libro->getActiveSheet()->getPageSetup()->getScale();

            if ($escala > 0 && $escala < 100) {
                $writer->setEditHtmlCallback(fn (string $html): string => $this->aplicarEscala($html, $escala / 100));
            }

            $writer->save($rutaAbsoluta);
        } catch (\Throwable $exception) {
            $disco->delete($rutaRelativa);

            throw $exception;
        }

        return $rutaAbsoluta;
    }

    private function aplicarEscala(string $html, float $escala): string
    {
        $html = preg_replace_callback('/\b(cellpadding|cellspacing)\s*=\s*(["\'])(-?(?:\d+\.?\d*|\.\d+))\2/i', function (array $coincidencia) use ($escala): string {
            $valor = round((float) $coincidencia[3] * $escala, 4);

            return $coincidencia[1].'='.$coincidencia[2].$valor.$coincidencia[2];
        }, $html) ?? $html;

        $propiedades = '(?:font-size|width|height|min-width|max-width|min-height|max-height|padding(?:-(?:top|right|bottom|left))?|border(?:-(?:top|right|bottom|left))?(?:-width)?|line-height|letter-spacing|word-spacing)';
        $patron = '/(?<propiedad>'.$propiedades.')\s*:\s*(?<valor>-?(?:\d+\.?\d*|\.\d+))(?<unidad>px|pt|in|cm|mm)\b/i';

        return preg_replace_callback($patron, function (array $coincidencia) use ($escala): string {
            $valor = round((float) $coincidencia['valor'] * $escala, 4);

            return $coincidencia['propiedad'].': '.$valor.$coincidencia['unidad'];
        }, $html) ?? $html;
    }
}
