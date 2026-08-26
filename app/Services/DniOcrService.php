<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;

class DniOcrService
{
    public function __construct(private readonly DniOcrTextExtractor $extractor)
    {
    }

    public function analizar(UploadedFile $archivo): array
    {
        if (! config('services.dni_ocr.enabled')) {
            return $this->sinResultado('La lectura automática no está habilitada. Ingresa los datos manualmente.');
        }

        $binario = (string) config('services.dni_ocr.tesseract_path');
        $tessdataPath = (string) config('services.dni_ocr.tessdata_path');

        if (! $this->binarioValido($binario)) {
            return $this->sinResultado('La lectura automática no está disponible temporalmente. Ingresa los datos manualmente.');
        }

        if ($tessdataPath === '' || ! is_dir($tessdataPath)) {
            return $this->sinResultado('La lectura automática no está disponible temporalmente. Ingresa los datos manualmente.');
        }

        $ruta = $archivo->getRealPath();
        if (! is_string($ruta) || $ruta === '') {
            return $this->sinResultado('No se pudo leer el documento. Selecciona otro archivo e inténtalo nuevamente.');
        }

        try {
            $lecturas = $this->lecturasPrincipales($binario, $tessdataPath, $ruta);
            $campos = $this->extractor->extraer($lecturas);

            if (! $campos['dni'] || ! $campos['nombres']) {
                $lecturas = array_merge(
                    $lecturas,
                    $this->lecturasDeRescate($binario, $tessdataPath, $ruta, $campos)
                );
                $campos = $this->extractor->extraer($lecturas);
            }
        } catch (\Throwable) {
            return $this->sinResultado('No se pudo leer el documento. Verifica que el DNI esté completo, enfocado y legible.');
        }

        if (! $campos['dni'] && ! $campos['nombres']) {
            return $this->sinResultado('No pudimos detectar los datos. Prueba con una imagen o PDF donde el DNI esté completo, recto y legible.');
        }

        $completo = (bool) ($campos['dni'] && $campos['nombres']);

        return [
            'detected' => true,
            'complete' => $completo,
            'dni' => $campos['dni'],
            'nombres' => $campos['nombres'],
            'message' => $completo
                ? 'Datos detectados. Revísalos y corrígelos si fuera necesario antes de enviar.'
                : 'Detectamos parte de los datos. Completa o corrige los campos antes de enviar.',
        ];
    }

    private function lecturasPrincipales(string $binario, string $tessdataPath, string $ruta): array
    {
        return array_values(array_filter([
            $this->ejecutar($binario, $tessdataPath, $ruta, 6),
            $this->ejecutar($binario, $tessdataPath, $ruta, 11),
            $this->ejecutar($binario, $tessdataPath, $ruta, 3, [
                'thresholding_method' => '2',
                'preserve_interword_spaces' => '1',
            ]),
            $this->ejecutar($binario, $tessdataPath, $ruta, 11, [
                'tessedit_char_whitelist' => '0123456789-',
            ]),
        ]));
    }

    private function lecturasDeRescate(string $binario, string $tessdataPath, string $ruta, array $campos): array
    {
        $lecturas = [];

        if (! $campos['nombres']) {
            $lecturas[] = $this->ejecutar($binario, $tessdataPath, $ruta, 12, [
                'thresholding_method' => '2',
                'preserve_interword_spaces' => '1',
            ]);
        }

        if (! $campos['dni']) {
            $lecturas[] = $this->ejecutar($binario, $tessdataPath, $ruta, 12, [
                'thresholding_method' => '2',
                'tessedit_char_whitelist' => '0123456789-',
            ]);
        }

        return array_values(array_filter($lecturas));
    }

    private function ejecutar(
        string $binario,
        string $tessdataPath,
        string $ruta,
        int $psm,
        array $configuracion = []
    ): ?array {
        $argumentos = [
            $binario,
            $ruta,
            'stdout',
            '-l',
            (string) config('services.dni_ocr.language', 'spa'),
            '--psm',
            (string) $psm,
            '--dpi',
            (string) config('services.dni_ocr.dpi', 300),
            '-c',
            'tessedit_create_tsv=1',
        ];

        foreach ($configuracion as $clave => $valor) {
            $argumentos[] = '-c';
            $argumentos[] = $clave.'='.$valor;
        }

        $proceso = new Process($argumentos);
        $proceso->setEnv(['TESSDATA_PREFIX' => $tessdataPath]);
        $proceso->setTimeout((float) config('services.dni_ocr.pass_timeout', 8));
        $proceso->run();

        if (! $proceso->isSuccessful()) {
            return null;
        }

        return $this->parsearTsv($proceso->getOutput());
    }

    private function parsearTsv(string $tsv): ?array
    {
        $lineas = [];

        foreach (preg_split('/\R/', $tsv) ?: [] as $fila) {
            if ($fila === '' || str_starts_with($fila, 'level\t')) {
                continue;
            }

            $columnas = explode("\t", $fila, 12);
            if (count($columnas) < 12 || (int) $columnas[0] !== 5) {
                continue;
            }

            $texto = trim($columnas[11]);
            $confianza = (float) $columnas[10];

            if ($texto === '' || $confianza < 0) {
                continue;
            }

            $clave = implode(':', [$columnas[1], $columnas[2], $columnas[3], $columnas[4]]);

            if (! isset($lineas[$clave])) {
                $lineas[$clave] = [
                    'palabras' => [],
                    'confianzas' => [],
                ];
            }

            $lineas[$clave]['palabras'][] = $texto;
            $lineas[$clave]['confianzas'][] = $confianza;
        }

        $resultado = [];

        foreach ($lineas as $linea) {
            $confianzas = $linea['confianzas'];
            if ($confianzas === []) {
                continue;
            }

            $resultado[] = [
                'texto' => implode(' ', $linea['palabras']),
                'confianza' => array_sum($confianzas) / count($confianzas),
                'confianza_minima' => min($confianzas),
            ];
        }

        return $resultado !== [] ? ['lineas' => $resultado] : null;
    }

    private function binarioValido(string $binario): bool
    {
        if ($binario === '') {
            return false;
        }

        if (str_contains($binario, DIRECTORY_SEPARATOR) || preg_match('/^[A-Z]:[\\\/]/i', $binario)) {
            return is_file($binario);
        }

        return $this->ejecutableDisponible($binario);
    }

    private function ejecutableDisponible(string $binario): bool
    {
        $proceso = new Process([$binario, '--version']);
        $proceso->setTimeout(3);

        try {
            $proceso->run();

            return $proceso->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function sinResultado(string $mensaje): array
    {
        return [
            'detected' => false,
            'complete' => false,
            'dni' => null,
            'nombres' => null,
            'message' => $mensaje,
        ];
    }
}
