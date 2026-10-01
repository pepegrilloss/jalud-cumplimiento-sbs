<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #202820; font-size: 9px; }
        h1 { font-size: 18px; margin: 0 0 3px; }
        h2 { font-size: 12px; margin: 18px 0 7px; padding: 5px 7px; background: #edf4df; border-left: 3px solid #91bd2e; }
        p { color: #596259; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d9ded5; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f5f7f3; width: 28%; font-weight: bold; }
        .intro { border-bottom: 1px solid #d9ded5; padding-bottom: 9px; }
        .muted { color: #687168; }
        .break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="intro">
        <h1>Registro de Operaciones (ROP) · {{ $rop->NumeroRop }}</h1>
        <p>Vista interna del registro, datos regulatorios ingresados, snapshot de JALUD y trazabilidad de evidencias.</p>
    </div>

    <h2>1. Información del ROP</h2>
    <table>
        <tr><th>Número ROP</th><td>{{ $rop->NumeroRop }}</td><th>Fecha de operación</th><td>{{ $rop->FechaOperacion?->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Estado</th><td>{{ \App\Models\RopRegistro::estados()[$rop->Estado] ?? $rop->Estado }}</td><th>Período</th><td>{{ $rop->Periodo?->format('m/Y') }}</td></tr>
        <tr><th>Umbral aplicado</th><td>S/ {{ number_format((float) $rop->UmbralAplicado, 2) }}</td><th>Sede / Zona</th><td>{{ data_get($rop->SnapshotDatos, 'sede.Nombre', '—') }} / {{ data_get($rop->SnapshotDatos, 'sede.Zona', '—') }}</td></tr>
    </table>

    <h2>2. Cliente</h2>
    <table>
        <tr><th>Nombre</th><td>{{ $rop->ClienteNombre }}</td><th>Documento</th><td>{{ $rop->NumeroDocumento ?: '—' }}</td></tr>
        <tr><th>Nacimiento</th><td>{{ data_get($rop->SnapshotDatos, 'cliente.FechaNacimiento', '—') }}</td><th>Actividad</th><td>{{ data_get($rop->SnapshotDatos, 'cliente.ProfesionOcupacion') ?: data_get($rop->SnapshotDatos, 'cliente.ActividadEconomica', '—') }}</td></tr>
        <tr><th>Domicilio</th><td colspan="3">{{ collect([data_get($rop->SnapshotDatos, 'cliente.Domicilio'), data_get($rop->SnapshotDatos, 'cliente.Departamento'), data_get($rop->SnapshotDatos, 'cliente.Provincia'), data_get($rop->SnapshotDatos, 'cliente.Distrito')])->filter()->join(' / ') ?: '—' }}</td></tr>
        <tr><th>Teléfono / correo</th><td colspan="3">{{ collect([data_get($rop->SnapshotDatos, 'cliente.Telefono1'), data_get($rop->SnapshotDatos, 'cliente.Correo')])->filter()->join(' · ') ?: '—' }}</td></tr>
    </table>

    <h2>3. Crédito / préstamo</h2>
    <table>
        <tr><th>Código de crédito</th><td>{{ $rop->CodigoCredito }}</td><th>Tipo</th><td>{{ data_get($rop->SnapshotDatos, 'credito.TipoCredito', '—') }}</td></tr>
        <tr><th>Monto del crédito</th><td>S/ {{ number_format((float) $rop->Monto, 2) }}</td><th>Estado en JALUD</th><td>{{ data_get($rop->SnapshotDatos, 'credito.Estado', '—') }}</td></tr>
        <tr><th>Plazo / cuotas</th><td>{{ data_get($rop->SnapshotDatos, 'credito.Plazo', '—') }} / {{ data_get($rop->SnapshotDatos, 'credito.NumeroCuotas', '—') }}</td><th>Monto de cuota</th><td>S/ {{ number_format((float) data_get($rop->SnapshotDatos, 'credito.MontoCuota', 0), 2) }}</td></tr>
        <tr><th>Desembolso</th><td>{{ data_get($rop->SnapshotDatos, 'credito.FechaDesembolso', '—') }}</td><th>Saldo pendiente</th><td>S/ {{ number_format((float) data_get($rop->SnapshotDatos, 'credito.SaldoPendiente', 0), 2) }}</td></tr>
    </table>

    <h2>4. Operación</h2>
    <table>
        <tr><th>Fecha</th><td>{{ $rop->FechaOperacion?->format('d/m/Y H:i') }}</td><th>Moneda</th><td>{{ $rop->Moneda }}</td></tr>
        <tr><th>Tipo</th><td>{{ data_get($rop->SnapshotDatos, 'operacion.TipoOperacionDetalle', 'Préstamo') }}</td><th>Forma de desembolso</th><td>{{ collect(data_get($rop->SnapshotDatos, 'operacion.FormaPagoDesembolso', []))->join(', ') ?: '—' }}</td></tr>
        <tr><th>Información adicional</th><td colspan="3">{{ data_get($rop->SnapshotDatos, 'operacion.InformacionAdicional', '—') }}</td></tr>
    </table>

    <h2>5. Pagos relacionados según snapshot</h2>
    @if (count(data_get($rop->SnapshotDatos, 'pagos', [])))
        <table>
            <thead><tr><th>Pago</th><th>Fecha</th><th>Monto</th><th>Medio / concepto</th><th>Comentario</th></tr></thead>
            <tbody>
                @foreach (data_get($rop->SnapshotDatos, 'pagos', []) as $pago)
                    <tr><td>{{ $pago['PagoID'] ?? '—' }}</td><td>{{ $pago['FechaPago'] ?? '—' }}</td><td>S/ {{ number_format((float) ($pago['MontoPagado'] ?? 0), 2) }}</td><td>{{ collect([$pago['TipoPago'] ?? null, $pago['TipoConcepto'] ?? null])->filter()->join(' / ') ?: '—' }}</td><td>{{ $pago['Comentario'] ?? '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">El snapshot no contenía pagos relacionados.</p>
    @endif

    <h2>6. Garantía</h2>
    <table><tr><th>Tipo</th><td>{{ data_get($rop->SnapshotDatos, 'garantia.Tipo', 'Sin información') }}</td><th>Documento</th><td>{{ data_get($rop->SnapshotDatos, 'garantia.Documento', '—') }}</td></tr><tr><th>Garante</th><td>{{ data_get($rop->SnapshotDatos, 'garantia.Nombre', '—') }}</td><th>Domicilio</th><td>{{ data_get($rop->SnapshotDatos, 'garantia.Domicilio', '—') }}</td></tr></table>

    <h2>7. Calificación</h2>
    <table>
        <tr><th>Resultado</th><td>{{ $rop->Calificacion ? (\App\Models\RopRegistro::calificaciones()[$rop->Calificacion] ?? $rop->Calificacion) : 'Pendiente' }}</td><th>Fecha</th><td>{{ $rop->CalificadoEn?->format('d/m/Y H:i:s') ?: '—' }}</td></tr>
        <tr><th>Motivo</th><td colspan="3">{{ $rop->MotivoCalificacion ?: '—' }}</td></tr>
        <tr><th>Observaciones</th><td colspan="3">{{ $rop->ObservacionesCalificacion ?: '—' }}</td></tr>
    </table>

    <div class="break"></div>
    <h2>8. Datos del formato oficial ROP</h2>
    <table>
        @forelse ($datosRegulatorios as $campo => $valor)
            <tr><th>{{ $campo }}</th><td>{{ $valor }}</td></tr>
        @empty
            <tr><td colspan="2">Aún no se han ingresado datos regulatorios.</td></tr>
        @endforelse
    </table>

    <h2>9. Evidencias adjuntas</h2>
    @if ($rop->adjuntos->isNotEmpty())
        <table>
            <thead><tr><th>Archivo</th><th>Descripción</th><th>Tipo</th><th>Subido por / fecha</th></tr></thead>
            <tbody>@foreach ($rop->adjuntos as $adjunto)<tr><td>{{ $adjunto->NombreOriginal }}</td><td>{{ $adjunto->Descripcion ?: '—' }}</td><td>{{ $adjunto->TipoMime ?: '—' }}</td><td>{{ $adjunto->subidoPor?->name ?: 'Sistema' }} · {{ $adjunto->created_at?->format('d/m/Y H:i') }}</td></tr>@endforeach</tbody>
        </table>
    @else
        <p class="muted">No se adjuntaron evidencias al ROP.</p>
    @endif

    <h2>10. Auditoría</h2>
    @if ($rop->auditorias->isNotEmpty())
        <table>
            <thead><tr><th>Fecha y hora</th><th>Acción</th><th>Usuario</th><th>Motivo</th></tr></thead>
            <tbody>@foreach ($rop->auditorias as $auditoria)<tr><td>{{ $auditoria->created_at?->format('d/m/Y H:i:s') }}</td><td>{{ $auditoria->Accion }}</td><td>{{ $auditoria->usuario?->name ?: 'Sistema' }}</td><td>{{ $auditoria->Motivo ?: '—' }}</td></tr>@endforeach</tbody>
        </table>
    @else
        <p class="muted">No hay movimientos de auditoría registrados.</p>
    @endif
</body>
</html>
