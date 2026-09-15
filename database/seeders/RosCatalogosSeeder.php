<?php

namespace Database\Seeders;

use App\Models\RosCatalogoItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RosCatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $hoja = IOFactory::load(resource_path('templates/ros/modelo-ros-sbs.xls'))->getSheetByName('FORMATO');

        RosCatalogoItem::query()->whereIn('Tipo', [
            'DELITO_PRECEDENTE', 'TIPO_SUJETO', 'ESTADO_CIVIL', 'CONDICION_PARTICIPACION',
            'VIA', 'TIPO_DOCUMENTO', 'MONEDA', 'TIPOLOGIA', 'SECTOR', 'ACTIVIDAD',
            'PRODUCTO_SERVICIO', 'SENAL_REPORTADO', 'SENAL_OPERACION',
        ])->update(['Activo' => false]);

        $this->rango($hoja, 'DELITO_PRECEDENTE', 'A', 373, 391);
        $this->rango($hoja, 'TIPO_SUJETO', 'F', 373, 393);
        $this->rango($hoja, 'ESTADO_CIVIL', 'L', 373, 379);
        $this->rango($hoja, 'CONDICION_PARTICIPACION', 'N', 373, 385);
        $this->rango($hoja, 'VIA', 'L', 394, 408);
        $this->rango($hoja, 'TIPO_DOCUMENTO', 'G', 398, 407);
        $this->rango($hoja, 'MONEDA', 'A', 403, 408);
        $this->rango($hoja, 'TIPOLOGIA', 'A', 414, 433);
        $this->sectores($hoja);
        $this->productos($hoja);
        $this->senales($hoja, 'SENAL_REPORTADO', 468, 503);
        $this->senales($hoja, 'SENAL_OPERACION', 506, 650);
    }

    private function rango(Worksheet $hoja, string $tipo, string $columna, int $desde, int $hasta): void
    {
        $orden = 0;

        for ($fila = $desde; $fila <= $hasta; $fila++) {
            $nombre = trim((string) $hoja->getCell("{$columna}{$fila}")->getValue());

            if ($tipo === 'TIPO_SUJETO' && $fila === 379) {
                $nombre .= ' '.trim((string) $hoja->getCell('F380')->getValue());
            }

            if ($tipo === 'TIPO_SUJETO' && $fila === 380) {
                continue;
            }

            if ($nombre === '' || str_starts_with($nombre, 'Numeral') || str_starts_with($nombre, 'Tabla de')) {
                continue;
            }

            $this->guardar($tipo, $this->codigo($tipo, $nombre, ++$orden), $nombre, null, $orden);
        }
    }

    private function sectores(Worksheet $hoja): void
    {
        $sector = null;
        $ordenSector = 0;
        $ordenActividad = 0;

        for ($fila = 415; $fila <= 462; $fila++) {
            $nuevoSector = trim((string) $hoja->getCell("I{$fila}")->getValue());
            $actividad = trim((string) $hoja->getCell("K{$fila}")->getValue());

            if ($nuevoSector !== '') {
                $sector = $nuevoSector;
                $this->guardar('SECTOR', $this->codigo('SECTOR', $sector, ++$ordenSector), $sector, null, $ordenSector);
            }

            if ($actividad !== '') {
                $this->guardar('ACTIVIDAD', $this->codigo('ACTIVIDAD', $actividad, ++$ordenActividad), $actividad, $sector, $ordenActividad);
            }
        }
    }

    private function productos(Worksheet $hoja): void
    {
        $orden = 0;

        foreach ([105, 107, 109, 111, 113, 115, 117, 119] as $fila) {
            foreach (['A', 'D', 'H', 'M'] as $columna) {
                $valor = trim((string) $hoja->getCell("{$columna}{$fila}")->getValue());

                if (!preg_match('/^([a-z]{1,2})\.\s*(.+)$/iu', $valor, $partes)) {
                    continue;
                }

                $this->guardar('PRODUCTO_SERVICIO', Str::lower($partes[1]), trim($partes[2]), null, ++$orden);
            }
        }
    }

    private function senales(Worksheet $hoja, string $tipo, int $desde, int $hasta): void
    {
        $grupo = null;
        $orden = 0;
        $otros = 0;

        for ($fila = $desde; $fila <= $hasta; $fila++) {
            $grupoCelda = trim((string) $hoja->getCell("A{$fila}")->getValue());
            $codigo = trim((string) $hoja->getCell("B{$fila}")->getValue());
            $nombre = trim((string) $hoja->getCell("C{$fila}")->getValue());

            if ($grupoCelda !== '') {
                $grupo = preg_replace('/\s+/', ' ', $grupoCelda);
            }

            if ($nombre === '') {
                continue;
            }

            if ($codigo === '') {
                $codigo = 'OTRO-'.++$otros;
            }

            $this->guardar($tipo, $codigo, $nombre, $grupo, ++$orden);
        }
    }

    private function guardar(string $tipo, string $codigo, string $nombre, ?string $grupo, int $orden): void
    {
        RosCatalogoItem::query()->updateOrCreate(
            ['Tipo' => $tipo, 'Codigo' => mb_substr($codigo, 0, 30)],
            ['Nombre' => $nombre, 'Grupo' => $grupo, 'Activo' => true, 'Orden' => $orden, 'VersionNormativa' => 'SBS-6561-2009']
        );
    }

    private function codigo(string $tipo, string $nombre, int $orden): string
    {
        $normalizado = Str::upper(Str::ascii($nombre));
        $mapas = [
            'MONEDA' => ['SOLES' => 'PEN', 'DOLARES' => 'USD', 'EUROS' => 'EUR', 'OTROS (SENALAR)' => 'OTRA'],
            'ESTADO_CIVIL' => ['SOLTERO' => 'SOLTERO', 'CASADO' => 'CASADO', 'VIUDO' => 'VIUDO', 'DIVORCIADO' => 'DIVORCIADO', 'CONCUBINO' => 'CONCUBINO', 'OTROS (SENALAR)' => 'OTRO'],
            'TIPO_DOCUMENTO' => [
                'CARNET DE EXTRANJERIA' => 'CE', 'CARNET DE IDENTIDAD' => 'CI', 'CEDULA DE CIUDADANIA' => 'CC',
                'CEDULA DIPLOMATICA DE IDENTIDAD' => 'CDI', 'DOCUMENTO NACIONAL DE IDENTIDAD' => 'DNI',
                'PASAPORTE' => 'PASAPORTE', 'REGISTRO UNICO DE CONTRIBUYENTES-RUC' => 'RUC', 'OTROS (SENALAR)' => 'OTRO',
            ],
        ];

        if (isset($mapas[$tipo][$normalizado])) {
            return $mapas[$tipo][$normalizado];
        }

        if ($tipo === 'CONDICION_PARTICIPACION') {
            return mb_substr(Str::upper(Str::slug($nombre, '_')), 0, 30);
        }

        $codigo = Str::upper(Str::slug($nombre, '_'));

        return mb_substr(str_pad((string) $orden, 3, '0', STR_PAD_LEFT).'_'.($codigo !== '' ? $codigo : 'ITEM'), 0, 30);
    }
}
