<?php

namespace App\Services\Cumplimiento;

use App\Models\RopRegistro;
use App\Models\RosCaso;
use App\Services\Rop\RopCompletenessService;
use App\Services\Rop\RopExcelExportService;
use App\Services\Ros\RosExcelExportService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class RopRosZipExportService
{
    public function __construct(
        private readonly RopExcelExportService $ropExportador,
        private readonly RosExcelExportService $rosExportador,
        private readonly RopCompletenessService $completitud,
    ) {}

    public function generar(CarbonInterface $desde, CarbonInterface $hasta): string
    {
        $desde = CarbonImmutable::instance($desde)->startOfDay();
        $hasta = CarbonImmutable::instance($hasta)->endOfDay();
        $archivosTemporales = [];
        $ropExportados = 0;
        $ropOmitidos = [];
        $rosExportados = 0;
        $directorio = 'cumplimiento-sbs/reportes-temporales';
        Storage::disk('local')->makeDirectory($directorio);
        $rutaZip = Storage::disk('local')->path("{$directorio}/reportes-rop-ros-".Str::uuid().'.zip');

        $zip = new ZipArchive;
        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No fue posible crear el archivo ZIP de reportes.');
        }

        try {
            $this->consultaRop($desde, $hasta)->each(function (RopRegistro $rop) use ($zip, &$archivosTemporales, &$ropExportados, &$ropOmitidos): void {
                if (! $this->completitud->estaCompleto($rop)) {
                    $ropOmitidos[] = $rop->NumeroRop;

                    return;
                }

                $ruta = $this->ropExportador->generar($rop);
                $archivosTemporales[] = $ruta;
                $zip->addFile($ruta, 'ROP/'.$this->nombreSeguro($rop->NumeroRop).'.xlsx');
                $ropExportados++;
            });

            $this->consultaRos($desde, $hasta)->each(function (RosCaso $ros) use ($zip, &$archivosTemporales, &$rosExportados): void {
                $ruta = $this->rosExportador->generar($ros);
                $archivosTemporales[] = $ruta;
                $zip->addFile($ruta, 'ROS/'.$this->nombreSeguro($ros->CodigoInterno).'.xls');
                $rosExportados++;
            });

            $zip->addFromString('RESUMEN.txt', $this->resumen($desde, $hasta, $ropExportados, $rosExportados, $ropOmitidos));

            if (! $zip->close()) {
                throw new RuntimeException('No fue posible finalizar el archivo ZIP de reportes.');
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($rutaZip);

            throw $exception;
        } finally {
            foreach ($archivosTemporales as $archivo) {
                @unlink($archivo);
            }
        }

        return $rutaZip;
    }

    private function consultaRop(CarbonInterface $desde, CarbonInterface $hasta): Builder
    {
        return RopRegistro::query()->whereBetween('FechaOperacion', [$desde, $hasta])
            ->orderBy('FechaOperacion')->orderBy('RopRegistroID');
    }

    private function consultaRos(CarbonInterface $desde, CarbonInterface $hasta): Builder
    {
        return RosCaso::query()
            ->whereDate('FechaOperacionDesde', '<=', $hasta->toDateString())
            ->where(function (Builder $query) use ($desde): void {
                $query->whereNull('FechaOperacionHasta')
                    ->orWhereDate('FechaOperacionHasta', '>=', $desde->toDateString());
            })
            ->orderBy('FechaOperacionDesde')->orderBy('RosCasoID');
    }

    private function nombreSeguro(?string $nombre): string
    {
        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre ?? 'SIN-CODIGO');

        return trim($nombre, '._-') ?: 'SIN-CODIGO';
    }

    /** @param array<int, string|null> $ropOmitidos */
    private function resumen(CarbonInterface $desde, CarbonInterface $hasta, int $ropExportados, int $rosExportados, array $ropOmitidos): string
    {
        $lineas = [
            'REPORTES DE CUMPLIMIENTO ROP Y ROS',
            "Rango de operaciones: {$desde->format('d/m/Y')} al {$hasta->format('d/m/Y')}",
            "ROP exportados: {$ropExportados}",
            "ROS exportados: {$rosExportados}",
            'ROP incompletos omitidos: '.count($ropOmitidos),
        ];

        if ($ropOmitidos !== []) {
            $lineas[] = '';
            $lineas[] = 'ROP omitidos por tener datos obligatorios pendientes:';
            array_push($lineas, ...array_map(fn (?string $numero): string => '- '.($numero ?: 'SIN NUMERO'), $ropOmitidos));
        }

        return implode(PHP_EOL, $lineas).PHP_EOL;
    }
}
