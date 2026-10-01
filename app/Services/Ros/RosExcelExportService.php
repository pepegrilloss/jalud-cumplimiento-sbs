<?php

namespace App\Services\Ros;

use App\Models\RosCaso;
use App\Models\RosCatalogoItem;
use App\Models\RosPersona;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class RosExcelExportService
{
    public function libro(RosCaso $caso): Spreadsheet
    {
        $caso->loadMissing([
            'personas.reportesPrevios', 'operaciones', 'senalesAlerta', 'tipologias', 'adjuntos',
        ]);

        $libro = IOFactory::load(resource_path('templates/ros/modelo-ros-sbs.xls'));
        $hoja = $libro->getSheetByName('FORMATO');

        if (! $hoja) {
            throw new \RuntimeException('La plantilla oficial ROS no contiene la hoja FORMATO.');
        }

        $this->limpiarDatosDeEjemplo($hoja);
        $this->llenarReporte($hoja, $caso);
        $libro->setActiveSheetIndex($libro->getIndex($hoja));
        $hoja->setSelectedCell('A1');

        return $libro;
    }

    public function generar(RosCaso $caso): string
    {
        $libro = $this->libro($caso);

        $directorio = 'cumplimiento-sbs/reportes-temporales';
        Storage::disk('local')->makeDirectory($directorio);
        $rutaRelativa = "{$directorio}/{$caso->CodigoInterno}-".Str::uuid().'.xls';
        (new Xls($libro))->save(Storage::disk('local')->path($rutaRelativa));

        return Storage::disk('local')->path($rutaRelativa);
    }

    private function limpiarDatosDeEjemplo(Worksheet $hoja): void
    {
        foreach (['F17', 'K17', 'N17', 'C264', 'H264'] as $celda) {
            $hoja->setCellValue($celda, null);
        }
    }

    private function llenarReporte(Worksheet $hoja, RosCaso $caso): void
    {
        $naturales = $caso->personas->where('TipoPersona', 'NATURAL')->values();
        $juridicas = $caso->personas->where('TipoPersona', 'JURIDICA')->values();
        $natural = $naturales->first();
        $juridica = $juridicas->first();

        $this->valor($hoja, 'B8', $caso->NumeroReporte);
        $this->fechaPartes($hoja, $caso->FechaReporte, ['I8', 'J8', 'K8']);
        $this->marca($hoja, 'C9', $caso->ClaseReporte === 'INICIAL');
        $this->marca($hoja, 'H9', $caso->ClaseReporte === 'CORRECCION');
        $this->marca($hoja, 'N9', $caso->ClaseReporte === 'AMPLIACION');
        $this->valor($hoja, 'G11', $caso->NumeroReporteAnterior);
        $this->fechaPartes($hoja, $caso->FechaReporteAnterior, ['G13', 'H13', 'I13']);
        $this->valor($hoja, 'G14', $this->catalogoNombre('DELITO_PRECEDENTE', $caso->DelitoPrecedente));

        $this->valor($hoja, 'F17', $this->catalogoNombre('TIPO_SUJETO', $caso->TipoSujetoObligado));
        $this->valor($hoja, 'K17', $caso->CodigoEmpresa);
        $this->valor($hoja, 'N17', $caso->CodigoOficialCumplimiento);
        $this->valor($hoja, 'E18', $caso->OficinaInformante);
        $this->valor($hoja, 'G20', $caso->OficinaOperacion);
        $this->valor($hoja, 'G22', $caso->DepartamentoOperacion);
        $this->valor($hoja, 'I22', $caso->ProvinciaOperacion);
        $this->valor($hoja, 'K22', $caso->DistritoOperacion);

        $this->valor($hoja, 'H28', $naturales->count());
        $this->valor($hoja, 'M28', $juridicas->count());

        if ($natural) {
            $this->llenarPersonaNatural($hoja, $natural);
        }

        if ($juridica) {
            $this->llenarPersonaJuridica($hoja, $juridica);
        }

        $personaRelacion = $natural ?? $juridica;
        if ($personaRelacion) {
            $this->llenarRelacionEmpresa($hoja, $personaRelacion);
        }

        $this->valor($hoja, 'D97', $this->catalogoNombre('MONEDA', $caso->Moneda));
        $this->valor($hoja, 'F97', $this->monto($caso->MontoTotal));
        $this->fecha($hoja, 'J97', $caso->FechaOperacionDesde);
        $this->fecha($hoja, 'N97', $caso->FechaOperacionHasta);
        $this->marca($hoja, 'F100', $caso->Alcance === 'NACIONAL');
        $this->marca($hoja, 'J100', $caso->Alcance === 'INTERNACIONAL');
        $this->valor($hoja, 'N101', $caso->Alcance === 'INTERNACIONAL' ? $caso->PaisesRelacionados : null);

        $productos = $caso->operaciones->pluck('CodigoProducto')->filter()->unique()->values();
        foreach ($this->celdasProductos() as $codigo => $celda) {
            if ($productos->contains($codigo)) {
                $this->prefijoX($hoja, $celda);
            }
        }

        $otros = $caso->operaciones->first(fn ($operacion) => $operacion->CodigoProducto === 'ff');
        if ($otros?->Detalle) {
            $this->valor($hoja, 'B122', $otros->Detalle);
        }

        foreach ($caso->operaciones->take(17)->values() as $indice => $operacion) {
            $fila = 125 + $indice;
            $producto = $operacion->ProductoServicio ?: $this->catalogoNombre('PRODUCTO_SERVICIO', $operacion->CodigoProducto);
            $this->valor($hoja, "C{$fila}", $producto);
            $this->valor($hoja, "F{$fila}", $operacion->NumeroOperacion);
            $this->valor($hoja, "I{$fila}", $this->catalogoNombre('MONEDA', $operacion->Moneda));
            $this->valor($hoja, "L{$fila}", $this->monto($operacion->Monto));
        }

        $this->valor($hoja, 'A144', $caso->DocumentacionComplementaria);

        $descripcion = collect([
            'a' => $caso->DescripcionHechos,
            'b' => $caso->RelacionPersonas,
            'c' => $caso->RelacionProductosServicios,
            'd' => $caso->CaracteristicasSospechosas,
            'e' => $caso->ExplicacionesJustificaciones,
            'f' => $caso->RelacionOtrasOperaciones,
            'g' => $caso->InformacionExcluida,
            'h' => $caso->CambiosReporteAnterior,
            'i' => $caso->FuentesConsultadas,
        ])->filter(fn ($valor) => filled($valor))
            ->map(fn ($valor, $letra) => $letra.'. '.trim((string) $valor))
            ->join("\n\n");
        $this->valor($hoja, 'A182', $descripcion);
        $hoja->getStyle('A182:P239')->getAlignment()->setWrapText(true)->setVertical('top');

        $this->valor($hoja, 'C248', $this->catalogoNombre('SECTOR', $caso->SectorEconomico));
        $this->valor($hoja, 'H248', $this->catalogoNombre('ACTIVIDAD', $caso->ActividadEconomica));

        $this->llenarSenales($hoja, $caso);
        $this->llenarTipologias($hoja, $caso);
    }

    private function llenarPersonaNatural(Worksheet $hoja, RosPersona $persona): void
    {
        $this->valor($hoja, 'A32', $persona->ApellidoPaterno);
        $this->valor($hoja, 'D32', $persona->ApellidoMaterno);
        $this->valor($hoja, 'G32', $persona->Nombres);
        $this->fecha($hoja, 'J32', $persona->FechaNacimiento);
        $this->valor($hoja, 'B33', $persona->Nacionalidad);
        $this->valor($hoja, 'E34', $persona->ProfesionOcupacion);
        $this->prefijoX($hoja, $persona->EsPep ? 'I33' : 'I34');
        $this->valor($hoja, 'K33', $this->catalogoNombre('TIPO_DOCUMENTO', $persona->TipoDocumento));
        $this->valor($hoja, 'O33', $persona->NumeroDocumento);
        $this->valor($hoja, 'M34', $persona->PaisEmisionDocumento);
        $this->valor($hoja, 'C35', $persona->PaisDomicilio);
        $this->valor($hoja, 'E36', $persona->DepartamentoDomicilio);
        $this->valor($hoja, 'H36', $persona->ProvinciaDomicilio);
        $this->valor($hoja, 'K36', $persona->DistritoDomicilio);
        $this->valor($hoja, 'N36', $persona->UrbanizacionSector);
        $this->valor($hoja, 'A38', $this->catalogoNombre('VIA', $persona->TipoVia));
        $this->valor($hoja, 'C38', $persona->NombreVia);
        $this->valor($hoja, 'G38', $persona->NumeroDomicilio);
        $this->valor($hoja, 'K38', $persona->Telefono);
        $this->valor($hoja, 'M38', $persona->Telefono2);
        $this->valor($hoja, 'O38', $persona->Telefono3);
        $this->valor($hoja, 'B39', $persona->Correo);
        $this->valor($hoja, 'F39', $this->catalogoNombre('ESTADO_CIVIL', $persona->EstadoCivil));
        $this->valor($hoja, 'I39', $persona->ConyugeApellidoPaterno);
        $this->valor($hoja, 'L39', $persona->ConyugeApellidoMaterno);
        $this->valor($hoja, 'O39', $persona->ConyugeNombres);

        $condiciones = array_filter(explode(',', (string) $persona->CondicionActividad));
        if (in_array('INDEPENDIENTE', $condiciones, true)) {
            $this->prefijoX($hoja, 'B41');
        }
        if (in_array('DEPENDIENTE', $condiciones, true)) {
            $this->prefijoX($hoja, 'D41');
        }
        if (in_array('NO_APLICA', $condiciones, true)) {
            $this->prefijoX($hoja, 'E41');
        }

        $this->valor($hoja, 'F41', $persona->Empleador);
        $this->valor($hoja, 'M40', $this->catalogoNombre('MONEDA', $persona->MonedaIngreso));
        $this->valor($hoja, 'M41', $this->monto($persona->IngresoMensual));
        $this->valor($hoja, 'N41', $persona->TelefonoLaboral);
        $this->valor($hoja, 'E42', $this->catalogoNombre('CONDICION_PARTICIPACION', $persona->CondicionParticipacion));
        $this->valor($hoja, 'L43', $persona->PartidaElectronica);
        $this->valor($hoja, 'O43', $persona->SedeRegistral);
        $this->llenarReportesPrevios($hoja, $persona, ['F46', 'F48', 'M46', 'M48']);
    }

    private function llenarPersonaJuridica(Worksheet $hoja, RosPersona $persona): void
    {
        $this->valor($hoja, 'C51', $persona->RazonSocial);
        $this->valor($hoja, 'O51', $persona->NumeroDocumento);
        $this->valor($hoja, 'B52', $persona->ObjetoSocial);
        $this->valor($hoja, 'K53', $persona->PartidaElectronica);
        $this->valor($hoja, 'O53', $persona->SedeRegistral);
        $this->valor($hoja, 'C55', $persona->PaisDomicilio);
        $this->valor($hoja, 'F55', $persona->DepartamentoDomicilio);
        $this->valor($hoja, 'H55', $persona->ProvinciaDomicilio);
        $this->valor($hoja, 'K55', $persona->DistritoDomicilio);
        $this->valor($hoja, 'N55', $persona->UrbanizacionSector);
        $this->valor($hoja, 'A57', $this->catalogoNombre('VIA', $persona->TipoVia));
        $this->valor($hoja, 'C57', $persona->NombreVia);
        $this->valor($hoja, 'H56', $persona->NumeroDomicilio);
        $this->valor($hoja, 'K57', $persona->Telefono);
        $this->valor($hoja, 'M57', $persona->Telefono2);
        $this->valor($hoja, 'O57', $persona->Telefono3);
        $this->valor($hoja, 'H58', $this->catalogoNombre('MONEDA', $persona->MonedaIngresoAnual));
        $this->valor($hoja, 'H59', $this->monto($persona->IngresoAnual));
        $this->valor($hoja, 'J59', $this->catalogoNombre('CONDICION_PARTICIPACION', $persona->CondicionParticipacion));
        $this->llenarReportesPrevios($hoja, $persona, ['F62', 'F65', 'M62', 'M65']);
        $this->llenarRepresentanteLegal($hoja, $persona);
    }

    private function llenarRepresentanteLegal(Worksheet $hoja, RosPersona $persona): void
    {
        $this->valor($hoja, 'A69', $persona->RepApellidoPaterno);
        $this->valor($hoja, 'D69', $persona->RepApellidoMaterno);
        $this->valor($hoja, 'G69', $persona->RepNombres);
        $this->fecha($hoja, 'J69', $persona->RepFechaNacimiento);
        $this->valor($hoja, 'A71', $persona->RepNacionalidad);
        $this->valor($hoja, 'E71', $persona->RepProfesionOcupacion);
        $this->valor($hoja, 'K70', $this->catalogoNombre('TIPO_DOCUMENTO', $persona->RepTipoDocumento));
        $this->valor($hoja, 'O70', $persona->RepNumeroDocumento);
        $this->valor($hoja, 'M71', $persona->RepPaisEmisionDocumento);
        $this->valor($hoja, 'B73', $persona->RepPaisDomicilio);
        $this->valor($hoja, 'E73', $persona->RepDepartamentoDomicilio);
        $this->valor($hoja, 'H73', $persona->RepProvinciaDomicilio);
        $this->valor($hoja, 'K73', $persona->RepDistritoDomicilio);
        $this->valor($hoja, 'N73', $persona->RepUrbanizacionSector);
        $this->valor($hoja, 'A75', $this->catalogoNombre('VIA', $persona->RepTipoVia));
        $this->valor($hoja, 'C75', $persona->RepNombreVia);
        $this->valor($hoja, 'G75', $persona->RepNumeroDomicilio);
        $this->valor($hoja, 'K75', $persona->RepTelefono);
        $this->valor($hoja, 'M75', $persona->RepTelefono2);
        $this->valor($hoja, 'O75', $persona->RepTelefono3);
        $this->valor($hoja, 'B76', $persona->RepCorreo);
        $this->valor($hoja, 'F76', $this->catalogoNombre('ESTADO_CIVIL', $persona->RepEstadoCivil));
        $this->valor($hoja, 'I76', $persona->RepConyugeApellidoPaterno);
        $this->valor($hoja, 'L76', $persona->RepConyugeApellidoMaterno);
        $this->valor($hoja, 'O76', $persona->RepConyugeNombres);
    }

    private function llenarReportesPrevios(Worksheet $hoja, RosPersona $persona, array $celdas): void
    {
        $reportes = $persona->reportesPrevios->values();
        if ($reportes->isEmpty()) {
            return;
        }

        [$numero, $fecha, $enviado, $informacion] = $celdas;
        $this->valor($hoja, $numero, $reportes->pluck('NumeroReporte')->filter()->join(', '));
        $this->valor($hoja, $fecha, $reportes->pluck('FechaReporte')->filter()->map(fn ($valor) => $valor->format('d/m/Y'))->join(', '));
        $this->valor($hoja, $enviado, $reportes->pluck('EnviadoA')->filter()->map(fn ($valor) => $this->enviadoSiNo($valor))->unique()->join(', '));
        $this->valor($hoja, $informacion, $reportes->pluck('InformacionRelevante')->filter()->join(' | '));
    }

    private function llenarRelacionEmpresa(Worksheet $hoja, RosPersona $persona): void
    {
        $celdas = [
            'CLIENTE' => 'B84',
            'DIRECTOR' => 'B86',
            'EMPLEADO' => 'F84',
            'ACCIONISTA' => 'F86',
            'INVERSIONISTA' => 'J84',
            'OTRO' => 'J86',
            'GERENTE' => 'N84',
        ];

        if (isset($celdas[$persona->RelacionEmpresa])) {
            $this->marca($hoja, $celdas[$persona->RelacionEmpresa], true);
        }
        $this->valor($hoja, 'M87', $persona->RelacionEmpresa === 'OTRO' ? $persona->RelacionEmpresaOtro : null);

        $this->marca($hoja, 'B90', $persona->VinculadoActualmente === true);
        $this->marca($hoja, 'B92', $persona->VinculadoActualmente === false);

        if ($persona->VinculadoActualmente === false) {
            $motivos = [
                'RETIRO_VOLUNTARIO' => 'F90',
                'DECISION_EMPRESA' => 'F92',
                'SUSPENSION' => 'M90',
            ];
            if (isset($motivos[$persona->MotivoDesvinculacion])) {
                $this->marca($hoja, $motivos[$persona->MotivoDesvinculacion], true);
            }
            $this->fecha($hoja, 'N92', $persona->FechaDesvinculacion);
        }
    }

    private function llenarSenales(Worksheet $hoja, RosCaso $caso): void
    {
        $grupos = [
            'REPORTADO' => [255, 256, 257],
            'OPERACION' => [259, 260, 261],
        ];

        foreach ($grupos as $tipo => $filas) {
            $senales = $caso->senalesAlerta->where('Tipo', $tipo)->values();
            foreach ($filas as $indice => $fila) {
                $senal = $senales->get($indice);
                if (! $senal) {
                    continue;
                }
                $catalogoTipo = $tipo === 'OPERACION' ? 'SENAL_OPERACION' : 'SENAL_REPORTADO';
                $grupo = RosCatalogoItem::query()->where('Tipo', $catalogoTipo)->where('Codigo', $senal->Codigo)->value('Grupo');
                $this->valor($hoja, "F{$fila}", $grupo);
                $this->valor($hoja, "J{$fila}", $senal->Codigo);
            }
        }
    }

    private function llenarTipologias(Worksheet $hoja, RosCaso $caso): void
    {
        $nombres = $caso->tipologias
            ->pluck('Codigo')
            ->filter()
            ->map(fn ($codigo) => $this->catalogoNombre('TIPOLOGIA', $codigo))
            ->filter()
            ->values();

        $hoja->setCellValue('C264', null);
        $hoja->setCellValue('H264', null);

        if ($nombres->isEmpty()) {
            return;
        }

        $this->valor($hoja, 'C264', $nombres->first());
        if ($nombres->count() > 1) {
            $this->valor($hoja, 'H264', $nombres->slice(1)->join('; '));
        }
    }

    private function celdasProductos(): array
    {
        $filas = [105, 107, 109, 111, 113, 115, 117, 119];
        $grupos = [
            'A' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'],
            'D' => ['i', 'j', 'k', 'l', 'm', 'n', 'o', 'p'],
            'H' => ['q', 'r', 's', 't', 'u', 'v', 'w', 'x'],
            'M' => ['y', 'z', 'aa', 'bb', 'cc', 'dd', 'ee', 'ff'],
        ];

        $celdas = [];
        foreach ($grupos as $columna => $codigos) {
            foreach ($codigos as $indice => $codigo) {
                $celdas[$codigo] = $columna.$filas[$indice];
            }
        }

        return $celdas;
    }

    private function catalogoNombre(string $tipo, ?string $codigo): ?string
    {
        if (blank($codigo)) {
            return null;
        }

        return RosCatalogoItem::query()->where('Tipo', $tipo)->where('Codigo', $codigo)->value('Nombre') ?: $codigo;
    }

    private function enviadoSiNo(?string $valor): ?string
    {
        if (blank($valor)) {
            return null;
        }

        return Str::upper($valor) === 'NO' ? 'NO' : 'SI';
    }

    private function fechaPartes(Worksheet $hoja, $fecha, array $celdas): void
    {
        if (! $fecha) {
            return;
        }

        $this->valor($hoja, $celdas[0], $fecha->format('d'));
        $this->valor($hoja, $celdas[1], $fecha->format('m'));
        $this->valor($hoja, $celdas[2], $fecha->format('Y'));
    }

    private function fecha(Worksheet $hoja, string $celda, $fecha): void
    {
        if ($fecha) {
            $this->valor($hoja, $celda, $fecha->format('d/m/Y'));
        }
    }

    private function monto($valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return round((float) $valor, 2);
    }

    private function marca(Worksheet $hoja, string $celda, bool $marcado): void
    {
        if ($marcado) {
            $this->valor($hoja, $celda, 'X');
        }
    }

    private function prefijoX(Worksheet $hoja, string $celda): void
    {
        $actual = trim((string) $hoja->getCell($celda)->getValue());
        if ($actual !== '' && ! str_starts_with($actual, 'X ')) {
            $hoja->setCellValue($celda, 'X '.$actual);
        }
    }

    private function valor(Worksheet $hoja, string $celda, $valor): void
    {
        if ($valor === null || $valor === '') {
            return;
        }

        $hoja->setCellValue($celda, $valor);
        $hoja->getStyle($celda)->getAlignment()->setWrapText(true)->setVertical('top');
    }
}
