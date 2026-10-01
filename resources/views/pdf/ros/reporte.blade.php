<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Vista previa ROS {{ $caso->NumeroReporte }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #172033; }
        h1 { font-size: 15px; margin: 0 0 4px; text-align: center; }
        .notice { margin: 0 0 10px; text-align: center; font-size: 8px; }
        h2 { font-size: 10px; padding: 5px 7px; margin: 10px 0 4px; border: 1px solid #9ba9b8; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #aeb9c7; padding: 4px; vertical-align: top; }
        th { text-align: left; }
        .meta td:first-child { width: 29%; font-weight: bold; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>REPORTE DE OPERACIONES SOSPECHOSAS</h1>
    <p class="notice">Vista previa interna. El archivo oficial para presentación es la exportación Excel sobre la plantilla FORMATO.</p>

    <h2>SECCIÓN I · Información del reporte</h2>
    <table class="meta">
        <tr><td>1. Número</td><td>{{ $caso->NumeroReporte }}</td></tr>
        <tr><td>2. Fecha</td><td>{{ $caso->FechaReporte?->format('d/m/Y') }}</td></tr>
        <tr><td>3. Clase</td><td>{{ $caso->ClaseReporte }}</td></tr>
        <tr><td>4. Referencia del reporte anterior</td><td>{{ $caso->NumeroReporteAnterior }}</td></tr>
        <tr><td>5. Fecha del reporte anterior</td><td>{{ $caso->FechaReporteAnterior?->format('d/m/Y') }}</td></tr>
        <tr><td>6. Presunto delito precedente</td><td>{{ $caso->DelitoPrecedente }}</td></tr>
    </table>

    <h2>SECCIÓN II · Información de la empresa que elabora el reporte</h2>
    <table class="meta">
        <tr><td>7. Ente Supervisor</td><td>No llenar en el formato oficial</td></tr>
        <tr><td>8. Tipo de Sujeto Obligado</td><td>{{ $caso->TipoSujetoObligado }}</td></tr>
        <tr><td>9. Código de la empresa SBS</td><td>{{ $caso->CodigoEmpresa }}</td></tr>
        <tr><td>10. Código Oficial de Cumplimiento</td><td>{{ $caso->CodigoOficialCumplimiento }}</td></tr>
        <tr><td>11. Oficina que informó</td><td>{{ $caso->OficinaInformante }}</td></tr>
        <tr><td>12. Oficina donde se realizó la operación</td><td>{{ $caso->OficinaOperacion }}</td></tr>
        <tr><td>13. Lugar</td><td>{{ collect([$caso->DepartamentoOperacion, $caso->ProvinciaOperacion, $caso->DistritoOperacion])->filter()->join(' / ') }}</td></tr>
    </table>

    <h2>SECCIÓN III · Personas que intervienen en la operación</h2>
    @foreach($caso->personas as $persona)
        @if($persona->TipoPersona === 'NATURAL')
            <table class="meta">
                <tr><td>14. Tipo</td><td>Persona natural</td></tr>
                <tr><td>15–17. Nombre</td><td>{{ trim("{$persona->ApellidoPaterno} {$persona->ApellidoMaterno} {$persona->Nombres}") }}</td></tr>
                <tr><td>18. Fecha de nacimiento</td><td>{{ $persona->FechaNacimiento?->format('d/m/Y') }}</td></tr>
                <tr><td>19. Nacionalidad</td><td>{{ $persona->Nacionalidad }}</td></tr>
                <tr><td>20. Profesión u ocupación</td><td>{{ $persona->ProfesionOcupacion }}</td></tr>
                <tr><td>20.a. PEP</td><td>{{ $persona->EsPep ? 'Sí' : 'No' }}</td></tr>
                <tr><td>21–23. Documento</td><td>{{ $persona->TipoDocumento }} {{ $persona->NumeroDocumento }} · {{ $persona->PaisEmisionDocumento }}</td></tr>
                <tr><td>24–31. Domicilio</td><td>{{ collect([$persona->PaisDomicilio, $persona->DepartamentoDomicilio, $persona->ProvinciaDomicilio, $persona->DistritoDomicilio, $persona->UrbanizacionSector, $persona->TipoVia, $persona->NombreVia, $persona->NumeroDomicilio])->filter()->join(' / ') }}</td></tr>
                <tr><td>32. Teléfonos</td><td>{{ collect([$persona->Telefono, $persona->Telefono2, $persona->Telefono3])->filter()->join(' / ') }}</td></tr>
                <tr><td>33. Correo electrónico</td><td>{{ $persona->Correo }}</td></tr>
                <tr><td>34–37. Estado civil / cónyuge</td><td>{{ collect([$persona->EstadoCivil, $persona->ConyugeApellidoPaterno, $persona->ConyugeApellidoMaterno, $persona->ConyugeNombres])->filter()->join(' / ') }}</td></tr>
                <tr><td>38–41. Actividad / ingresos</td><td>{{ collect([$persona->CondicionActividad, $persona->Empleador, $persona->MonedaIngreso, $persona->IngresoMensual, $persona->TelefonoLaboral])->filter()->join(' / ') }}</td></tr>
                <tr><td>42. Condición de intervención</td><td>{{ $persona->CondicionParticipacion }}</td></tr>
                <tr><td>43–44. SUNARP</td><td>{{ collect([$persona->PartidaElectronica, $persona->SedeRegistral])->filter()->join(' / ') }}</td></tr>
                <tr><td>45. Reportes anteriores</td><td>@foreach($persona->reportesPrevios as $reporte){{ $reporte->NumeroReporte }} · {{ $reporte->FechaReporte?->format('d/m/Y') }} · {{ $reporte->EnviadoA }} · {{ $reporte->InformacionRelevante }}@if(!$loop->last)<br>@endif @endforeach</td></tr>
            </table>
        @else
            <table class="meta">
                <tr><td>14. Tipo</td><td>Persona jurídica</td></tr>
                <tr><td>46. Denominación o razón social</td><td>{{ $persona->RazonSocial }}</td></tr>
                <tr><td>47. Número de R.U.C.</td><td>{{ $persona->NumeroDocumento }}</td></tr>
                <tr><td>48. Objeto social</td><td>{{ $persona->ObjetoSocial }}</td></tr>
                <tr><td>49–50. SUNARP</td><td>{{ collect([$persona->PartidaElectronica, $persona->SedeRegistral])->filter()->join(' / ') }}</td></tr>
                <tr><td>51–58. Domicilio</td><td>{{ collect([$persona->PaisDomicilio, $persona->DepartamentoDomicilio, $persona->ProvinciaDomicilio, $persona->DistritoDomicilio, $persona->UrbanizacionSector, $persona->TipoVia, $persona->NombreVia, $persona->NumeroDomicilio])->filter()->join(' / ') }}</td></tr>
                <tr><td>59. Teléfonos</td><td>{{ collect([$persona->Telefono, $persona->Telefono2, $persona->Telefono3])->filter()->join(' / ') }}</td></tr>
                <tr><td>60. Ingresos declarados</td><td>{{ collect([$persona->MonedaIngresoAnual, $persona->IngresoAnual])->filter()->join(' / ') }}</td></tr>
                <tr><td>61. Condición de intervención</td><td>{{ $persona->CondicionParticipacion }}</td></tr>
                <tr><td>62. Reportes anteriores</td><td>@foreach($persona->reportesPrevios as $reporte){{ $reporte->NumeroReporte }} · {{ $reporte->FechaReporte?->format('d/m/Y') }} · {{ $reporte->EnviadoA }} · {{ $reporte->InformacionRelevante }}@if(!$loop->last)<br>@endif @endforeach</td></tr>
                <tr><td>63–85. Representante legal</td><td>{{ trim("{$persona->RepApellidoPaterno} {$persona->RepApellidoMaterno} {$persona->RepNombres}") }} · {{ $persona->RepTipoDocumento }} {{ $persona->RepNumeroDocumento }}</td></tr>
            </table>
        @endif
        <table class="meta">
            <tr><td>86. Relación con la empresa</td><td>{{ $persona->RelacionEmpresa }} {{ $persona->RelacionEmpresaOtro }}</td></tr>
            <tr><td>87. Vinculado actualmente</td><td>{{ $persona->VinculadoActualmente ? 'Sí' : 'No' }}</td></tr>
            <tr><td>88. Motivo / fecha de desvinculación</td><td>{{ $persona->MotivoDesvinculacion }} · {{ $persona->FechaDesvinculacion?->format('d/m/Y') }}</td></tr>
        </table>
    @endforeach

    <h2>SECCIÓN IV · Información de la operación sospechosa</h2>
    <table class="meta">
        <tr><td>89. Monto total</td><td>{{ $caso->Moneda }} {{ $caso->MontoTotal }}</td></tr>
        <tr><td>90. Rango de fechas</td><td>{{ $caso->FechaOperacionDesde?->format('d/m/Y') }} — {{ $caso->FechaOperacionHasta?->format('d/m/Y') }}</td></tr>
        <tr><td>91. Alcance</td><td>{{ $caso->Alcance }} {{ $caso->PaisesRelacionados }}</td></tr>
    </table>
    <table>
        <thead><tr><th>92. Producto o servicio</th><th>93. N° o Código</th><th>93. Moneda</th><th>93. Monto</th></tr></thead>
        <tbody>
        @foreach($caso->operaciones as $operacion)
            <tr><td>{{ $operacion->CodigoProducto }} · {{ $operacion->ProductoServicio }}</td><td>{{ $operacion->NumeroOperacion }}</td><td>{{ $operacion->Moneda }}</td><td>{{ $operacion->Monto }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <table class="meta">
        <tr><td>94. Documentación complementaria</td><td>{!! nl2br(e($caso->DocumentacionComplementaria)) !!}</td></tr>
    </table>

    <div class="page-break"></div>
    <h2>SECCIÓN V · Descripción de la operación sospechosa</h2>
    <table class="meta">
        <tr><td>95.a.</td><td>{!! nl2br(e($caso->DescripcionHechos)) !!}</td></tr>
        <tr><td>95.b.</td><td>{!! nl2br(e($caso->RelacionPersonas)) !!}</td></tr>
        <tr><td>95.c.</td><td>{!! nl2br(e($caso->RelacionProductosServicios)) !!}</td></tr>
        <tr><td>95.d.</td><td>{!! nl2br(e($caso->CaracteristicasSospechosas)) !!}</td></tr>
        <tr><td>95.e.</td><td>{!! nl2br(e($caso->ExplicacionesJustificaciones)) !!}</td></tr>
        <tr><td>95.f.</td><td>{!! nl2br(e($caso->RelacionOtrasOperaciones)) !!}</td></tr>
        <tr><td>95.g.</td><td>{!! nl2br(e($caso->InformacionExcluida)) !!}</td></tr>
        <tr><td>95.h.</td><td>{!! nl2br(e($caso->CambiosReporteAnterior)) !!}</td></tr>
        <tr><td>95.i.</td><td>{!! nl2br(e($caso->FuentesConsultadas)) !!}</td></tr>
        <tr><td>96. Sector / actividad</td><td>{{ $caso->SectorEconomico }} · {{ $caso->ActividadEconomica }}</td></tr>
    </table>

    <h2>SECCIÓN VI · Señales de alerta y tipologías</h2>
    <table>
        <thead><tr><th>97. Tipo</th><th>97. Código</th></tr></thead>
        <tbody>@foreach($caso->senalesAlerta as $senal)<tr><td>{{ $senal->Tipo }}</td><td>{{ $senal->Codigo }}</td></tr>@endforeach</tbody>
    </table>
    <table class="meta"><tr><td>98. Posibles tipologías</td><td>{{ $caso->tipologias->pluck('Codigo')->filter()->join(', ') }}</td></tr></table>

    <h2>Evidencias adjuntas de uso interno</h2>
    @if ($caso->adjuntos->isNotEmpty())
        <table>
            <thead><tr><th>Archivo</th><th>Descripción</th><th>Tipo</th><th>Fecha</th></tr></thead>
            <tbody>
            @foreach ($caso->adjuntos as $adjunto)
                <tr><td>{{ $adjunto->NombreOriginal ?: basename($adjunto->RutaArchivo) }}</td><td>{{ $adjunto->Descripcion ?: '—' }}</td><td>{{ $adjunto->TipoMime ?: '—' }}</td><td>{{ $adjunto->created_at?->format('d/m/Y H:i') ?: '—' }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <p>El presente PDF enumera las evidencias; los archivos originales permanecen disponibles desde la ficha del caso.</p>
    @else
        <p>No hay evidencias adjuntas a este caso ROS.</p>
    @endif
</body>
</html>
