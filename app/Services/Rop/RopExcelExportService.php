<?php

namespace App\Services\Rop;

use App\Models\RopRegistro;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RopExcelExportService
{
    public function libro(RopRegistro $registro): Spreadsheet
    {
        $libro = IOFactory::load(resource_path('templates/rop/modelo-rop.xlsx'));
        $hoja = $libro->getSheetByName('Modelo ROP');

        if (! $hoja) {
            throw new \RuntimeException('La plantilla ROP no contiene la hoja Modelo ROP.');
        }

        $this->llenar($hoja, $registro);
        $this->prepararSalida($hoja, $registro);
        $libro->setActiveSheetIndex($libro->getIndex($hoja));
        $hoja->setSelectedCell('A1');
        $hoja->setTitle('Registro de Operaciones');

        return $libro;
    }

    private function prepararSalida(Worksheet $hoja, RopRegistro $registro): void
    {
        // La plantilla oficial incluye las tablas de referencia desde la fila 106.
        // Se conservan en el archivo fuente, pero no deben aparecer en el reporte generado.
        $ultimaFila = $hoja->getHighestDataRow();
        $hoja->getPageSetup()->setPrintArea('A1:Q104');

        // El archivo generado es un registro completado, no el modelo en blanco.
        $hoja->setCellValue('A9', 'REGISTRO DE OPERACIONES (ROP)');
        $hoja->setCellValue('A10', 'CORRELATIVO: '.$registro->NumeroRop);
        $hoja->getStyle('A9:Q9')->getFont()->setSize(18)->setBold(true);
        $hoja->getStyle('A9:Q9')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $hoja->getRowDimension(9)->setRowHeight(24);

        $estiloCorrelativo = $hoja->getStyle('A10:Q10');
        $estiloCorrelativo->getFont()->setSize(24)->setBold(true)->getColor()->setARGB('FF5B8C00');
        $estiloCorrelativo->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $estiloCorrelativo->getFill()->setFillType(Fill::FILL_SOLID);
        $estiloCorrelativo->getFill()->getStartColor()->setARGB('FFEAF5D7');
        $hoja->getRowDimension(10)->setRowHeight(32);

        for ($fila = 106; $fila <= $ultimaFila; $fila++) {
            $hoja->getRowDimension($fila)->setVisible(false);
        }
    }

    public function generar(RopRegistro $registro): string
    {
        $directorio = 'cumplimiento-sbs/reportes-temporales';
        Storage::disk('local')->makeDirectory($directorio);
        $ruta = "{$directorio}/{$registro->NumeroRop}-".Str::uuid().'.xlsx';
        (new Xlsx($this->libro($registro)))->save(Storage::disk('local')->path($ruta));

        return Storage::disk('local')->path($ruta);
    }

    private function llenar(Worksheet $hoja, RopRegistro $registro): void
    {
        $snapshot = $registro->SnapshotDatos ?? [];
        $datos = app(RopOfficialDataService::class)->datos($registro);

        $this->texto($hoja, 'C15', $registro->NumeroRop);
        $this->valor($hoja, 'H15', $registro->FechaOperacion?->format('d'));
        $this->valor($hoja, 'I15', $registro->FechaOperacion?->format('m'));
        $this->valor($hoja, 'J15', $registro->FechaOperacion?->format('Y'));
        $this->valor($hoja, 'P15', $datos['organismo_supervisor'] ?? null);
        $this->texto($hoja, 'C18', $datos['codigo_sujeto_obligado'] ?? 'S/C');
        $this->texto($hoja, 'J18', $datos['codigo_oficial_cumplimiento'] ?? 'S/C');
        $this->valor($hoja, 'P18', $this->catalogo($datos['tipo_sujeto_obligado'] ?? null));

        $this->valor($hoja, 'C25', (float) $registro->Monto);
        $moneda = $registro->Moneda;
        match ($moneda) {
            'PEN' => $this->prefijoX($hoja, 'G26'),
            'USD' => $this->prefijoX($hoja, 'H26'),
            'EUR' => $this->prefijoX($hoja, 'I26'),
            default => $this->valor($hoja, 'K26', $datos['moneda_otro'] ?? $moneda),
        };
        $this->valor($hoja, 'P25', isset($datos['tipo_cambio']) ? (float) $datos['tipo_cambio'] : null);

        $formas = $datos['formas_pago'] ?? [];
        foreach ([
            'AL_CONTADO' => 'C29', 'A_PLAZOS' => 'D29', 'EFECTIVO' => 'E29', 'CHEQUE' => 'F29',
            'GIROS' => 'G29', 'TRANSFERENCIA_BANCARIA' => 'H29', 'DEPOSITO_CUENTA' => 'J29',
            'TARJETA_CREDITO' => 'M29', 'OTROS' => 'N29',
        ] as $forma => $celda) {
            if (in_array($forma, $formas, true)) {
                $this->prefijoX($hoja, $celda);
            }
        }
        if (in_array('OTROS', $formas, true) && filled($datos['forma_pago_otro'] ?? null)) {
            $this->valor($hoja, 'N29', 'X  Otros: '.$datos['forma_pago_otro']);
        }

        $tiposOperacion = $datos['tipos_operacion'] ?? [];
        foreach ([
            'IMPORTACION_DEFINITIVA' => 'A33', 'EXPORTACION_DEFINITIVA' => 'A35', 'OPERACIONES_ADUANERAS' => 'A37',
            'ACTO_NOTARIAL' => 'A39', 'RECEPCION_FONDOS' => 'A41', 'EXPLOTACION_MINAS' => 'A43',
            'EXTRACCION_MINERALES' => 'A45', 'COMPRAVENTA_MINERALES' => 'A47', 'COMPRAVENTA_EMBARCACIONES' => 'A49',
            'COMPRAVENTA_AERONAVES' => 'G33', 'COMPRAVENTA_ARMAS' => 'G35', 'IMPORTACION_ARMAS' => 'G37',
            'FABRICACION_EXPLOSIVOS' => 'G39', 'COMERCIALIZACION_EXPLOSIVOS' => 'G41', 'CAJAS_SEGURIDAD' => 'G43',
            'AGENCIA_VIAJES' => 'G45', 'HOTELES' => 'G47', 'ADMISION_ENVIO_POSTAL' => 'G49',
            'REMESAS_GIROS' => 'M33', 'DONACIONES' => 'M36', 'PRODUCCION_INSUMOS_QUIMICOS' => 'M40',
            'COMERCIALIZACION_INSUMOS_QUIMICOS' => 'M43', 'OTRO' => 'M46',
        ] as $tipo => $celda) {
            if (in_array($tipo, $tiposOperacion, true)) {
                $this->prefijoX($hoja, $celda);
            } else {
                $hoja->setCellValue($celda, null);
            }
        }
        if (in_array('OTRO', $tiposOperacion, true) && filled($datos['tipo_operacion_otro'] ?? null)) {
            $this->valor($hoja, 'M46', 'X  w. Otro (especifique): '.$datos['tipo_operacion_otro']);
        }
        if (($datos['compra_venta'] ?? null) === 'COMPRA') {
            $this->prefijoX($hoja, 'I52');
        }
        if (($datos['compra_venta'] ?? null) === 'VENTA') {
            $this->prefijoX($hoja, 'N52');
        }
        $this->valor($hoja, 'G54', $datos['tipo_acto_contrato'] ?? null);
        $this->valor($hoja, 'G56', $datos['tipo_servicio'] ?? null);
        $this->prefijoX($hoja, ($datos['individual_multiple'] ?? 'INDIVIDUAL') === 'MULTIPLE' ? 'N58' : 'I58');
        $oportunidades = [
            'FIRMA_CONTRATO' => 'A62', 'ENTREGA_BIEN' => 'A64', 'AL_MOMENTO_OPERACION' => 'A66',
            'PRESTACION_SERVICIO' => 'E62', 'DESPACHO_ADUANERO' => 'E64', 'FIRMA_ACTA_TRANSFERENCIA' => 'E66',
            'FIRMA_INSTRUMENTO_NOTARIAL' => 'J62', 'ENTREGA_MERCADERIA' => 'J64', 'OTRO' => 'J66',
        ];
        if (isset($oportunidades[$datos['oportunidad_pago'] ?? ''])) {
            $this->prefijoX($hoja, $oportunidades[$datos['oportunidad_pago']]);
        }
        if (($datos['oportunidad_pago'] ?? null) === 'OTRO' && filled($datos['oportunidad_pago_otro'] ?? null)) {
            $this->valor($hoja, 'J66', 'X  i. Otros (Especifique): '.$datos['oportunidad_pago_otro']);
        }
        $this->valor($hoja, 'C68', $datos['informacion_adicional'] ?? null);

        if (($datos['tipo_persona'] ?? 'NATURAL') === 'JURIDICA') {
            $this->llenarJuridica($hoja, $datos['persona_juridica'] ?? []);
        } else {
            $this->llenarNatural($hoja, $datos['persona_natural'] ?? []);
        }
    }

    private function llenarNatural(Worksheet $hoja, array $cliente): void
    {
        $this->valor($hoja, 'A95', $cliente['apellido_paterno'] ?? null);
        $this->valor($hoja, 'C95', $cliente['apellido_materno'] ?? null);
        $this->valor($hoja, 'F95', $cliente['nombres'] ?? null);
        $this->valor($hoja, 'I95', $cliente['fecha_nacimiento'] ?? null);
        $this->valor($hoja, 'M95', $cliente['nacionalidad'] ?? null);
        $this->valor($hoja, 'N95', $cliente['profesion_ocupacion'] ?? null);
        $this->valor($hoja, 'C97', $cliente['tipo_documento'] ?? null);
        $this->texto($hoja, 'F97', $cliente['numero_documento'] ?? null);
        $this->valor($hoja, 'I97', $cliente['pais_emision_documento'] ?? null);
        $this->valor($hoja, 'D99', $cliente['departamento'] ?? null);
        $this->valor($hoja, 'G99', $cliente['provincia'] ?? null);
        $this->valor($hoja, 'J99', $cliente['distrito'] ?? null);
        $this->valor($hoja, 'O99', $cliente['urbanizacion_sector'] ?? null);
        $this->valor($hoja, 'C101', trim(($this->catalogo($cliente['tipo_via'] ?? null).' '.($cliente['nombre_via'] ?? ''))));
        $this->texto($hoja, 'I101', $cliente['numero_via'] ?? null);
        $this->valor($hoja, 'K101', $cliente['manzana_lote'] ?? null);
        $this->texto($hoja, 'O101', $cliente['telefono_1'] ?? null);
        $this->texto($hoja, 'Q101', $cliente['telefono_2'] ?? null);
        $estado = strtoupper((string) ($cliente['estado_civil'] ?? ''));
        $celda = ['SOLTERO' => 'C104', 'CASADO' => 'D104', 'VIUDO' => 'E104', 'DIVORCIADO' => 'F104'][$estado] ?? null;
        if ($celda) {
            $this->prefijoX($hoja, $celda);
        }
        $this->valor($hoja, 'M103', $cliente['conyuge'] ?? null);
    }

    private function llenarJuridica(Worksheet $hoja, array $cliente): void
    {
        $this->valor($hoja, 'C73', $cliente['razon_social'] ?? null);
        $this->texto($hoja, 'L73', $cliente['ruc'] ?? null);
        $this->valor($hoja, 'P73', $this->catalogo($cliente['actividad_economica'] ?? null));
        $this->valor($hoja, 'C75', $cliente['domicilio_extranjero'] ?? null);
        $this->valor($hoja, 'I75', $cliente['pais'] ?? null);
        $this->valor($hoja, 'N75', $cliente['distrito_estado_extranjero'] ?? null);
        $this->valor($hoja, 'D77', $cliente['departamento'] ?? null);
        $this->valor($hoja, 'G77', $cliente['provincia'] ?? null);
        $this->valor($hoja, 'J77', $cliente['distrito'] ?? null);
        $this->valor($hoja, 'O77', $cliente['urbanizacion_sector'] ?? null);
        $this->valor($hoja, 'C79', trim($this->catalogo($cliente['tipo_via'] ?? null).' '.($cliente['nombre_via'] ?? '')));
        $this->texto($hoja, 'I79', $cliente['numero_via'] ?? null);
        $this->valor($hoja, 'K79', $cliente['manzana_lote'] ?? null);
        $this->texto($hoja, 'O79', $cliente['telefono_1'] ?? null);
        $this->texto($hoja, 'Q79', $cliente['telefono_2'] ?? null);
        $this->texto($hoja, 'E81', $cliente['partida_electronica'] ?? null);
        $this->valor($hoja, 'J81', $cliente['sede_registral'] ?? null);
        $this->valor($hoja, 'N81', $cliente['registro'] ?? null);

        $r = $cliente['representante'] ?? [];
        foreach (['A85' => 'apellido_paterno', 'C85' => 'apellido_materno', 'F85' => 'nombres', 'I85' => 'fecha_nacimiento', 'M85' => 'nacionalidad', 'N85' => 'profesion_ocupacion', 'P85' => 'estado_civil', 'C87' => 'tipo_documento', 'H87' => 'numero_documento', 'L87' => 'pais_emision', 'D89' => 'departamento', 'G89' => 'provincia', 'J89' => 'distrito', 'O89' => 'urbanizacion_sector', 'I91' => 'numero_via', 'K91' => 'manzana_lote', 'O91' => 'telefono_1', 'Q91' => 'telefono_2'] as $celda => $campo) {
            $this->valor($hoja, $celda, $r[$campo] ?? null);
        }
        $this->valor($hoja, 'C91', trim($this->catalogo($r['tipo_via'] ?? null).' '.($r['nombre_via'] ?? '')));
    }

    private function catalogo(mixed $valor): string
    {
        if (! filled($valor)) {
            return '';
        }
        try {
            return \App\Models\RopCatalogoItem::query()->where('Codigo', $valor)->value('Nombre') ?: (string) $valor;
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    private function prefijoX(Worksheet $hoja, string $celda): void
    {
        $valor = trim((string) $hoja->getCell($celda)->getValue());
        if ($valor !== '' && ! str_starts_with($valor, 'X ')) {
            $hoja->setCellValue($celda, 'X  '.$valor);
        }
    }

    private function valor(Worksheet $hoja, string $celda, mixed $valor): void
    {
        if ($valor === null || $valor === '') {
            return;
        }
        $hoja->setCellValue($celda, $valor);
        $hoja->getStyle($celda)->getAlignment()->setWrapText(true)->setVertical('center');
    }

    private function texto(Worksheet $hoja, string $celda, mixed $valor): void
    {
        if ($valor === null || $valor === '') {
            return;
        }
        $hoja->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING);
        $hoja->getStyle($celda)->getAlignment()->setWrapText(true)->setVertical('center');
    }
}
