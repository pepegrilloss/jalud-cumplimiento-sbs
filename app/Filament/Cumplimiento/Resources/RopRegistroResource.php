<?php

namespace App\Filament\Cumplimiento\Resources;

use App\Filament\Cumplimiento\Resources\RopRegistroResource\Pages;
use App\Filament\Cumplimiento\Resources\RopRegistroResource\RelationManagers;
use App\Models\RopCatalogoItem;
use App\Models\RopRegistro;
use App\Services\Rop\RopCompletenessService;
use App\Services\Rop\RopQualificationService;
use App\Services\Rop\RopToRosService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RopRegistroResource extends Resource
{
    protected static ?string $model = RopRegistro::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Cumplimiento';

    protected static ?string $navigationLabel = 'Registro de Operaciones (ROP)';

    protected static ?string $modelLabel = 'ROP';

    protected static ?string $pluralModelLabel = 'Registro de Operaciones (ROP)';

    protected static ?string $recordTitleAttribute = 'NumeroRop';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->puedeAccederACumplimientoSbs() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        $tipoOperacion = [
            'IMPORTACION_DEFINITIVA' => 'a. Importación definitiva', 'EXPORTACION_DEFINITIVA' => 'b. Exportación definitiva',
            'OPERACIONES_ADUANERAS' => 'c. Otros regímenes u operaciones aduaneras', 'ACTO_NOTARIAL' => 'd. Acto/contrato en instrumento público notarial protocolar',
            'RECEPCION_FONDOS' => 'e. Recepción de fondos que no provengan del erario nacional', 'EXPLOTACION_MINAS' => 'f. Explotación de minas',
            'EXTRACCION_MINERALES' => 'g. Extracción de minerales', 'COMPRAVENTA_MINERALES' => 'h. Compraventa de minerales',
            'COMPRAVENTA_EMBARCACIONES' => 'i. Compraventa de embarcaciones', 'COMPRAVENTA_AERONAVES' => 'j. Compraventa de aeronaves',
            'COMPRAVENTA_ARMAS' => 'k. Compraventa de armas', 'IMPORTACION_ARMAS' => 'l. Importación de armas',
            'FABRICACION_EXPLOSIVOS' => 'm. Fabricación de materiales explosivos', 'COMERCIALIZACION_EXPLOSIVOS' => 'n. Comercialización de materiales explosivos',
            'CAJAS_SEGURIDAD' => 'o. Servicio de cajas de seguridad', 'AGENCIA_VIAJES' => 'p. Agencias de viaje y turismo',
            'HOTELES' => 'q. Hoteles (servicios)', 'ADMISION_ENVIO_POSTAL' => 'r. Admisión/envío postal por correo o courier',
            'REMESAS_GIROS' => 's. Envío de remesas/giros por correo o courier', 'DONACIONES' => 't. Donaciones/aportes de terceros',
            'PRODUCCION_INSUMOS_QUIMICOS' => 'u. Producción de insumos químicos para drogas o explosivos',
            'COMERCIALIZACION_INSUMOS_QUIMICOS' => 'v. Comercialización de insumos químicos para drogas o explosivos',
            'OTRO' => 'w. Otro',
        ];

        return $form->schema([
            Forms\Components\Section::make('SECCIÓN I · Información del ROP y del sujeto obligado')->schema([
                Forms\Components\TextInput::make('NumeroRop')->label('1. Número del ROP')->disabled(),
                Forms\Components\DateTimePicker::make('FechaOperacion')->label('2. Fecha')->disabled(),
                Forms\Components\TextInput::make('DatosRegulatorios.organismo_supervisor')->label('3. Organismo supervisor')->required(),
                Forms\Components\TextInput::make('DatosRegulatorios.codigo_sujeto_obligado')->label('4. Código del sujeto obligado')->required(),
                Forms\Components\TextInput::make('DatosRegulatorios.codigo_oficial_cumplimiento')->label('5. Código del Oficial de Cumplimiento')->required(),
                Forms\Components\Select::make('DatosRegulatorios.tipo_sujeto_obligado')->label('6. Tipo de sujeto obligado (Tabla N.° 1)')
                    ->options(fn (): array => RopCatalogoItem::opciones(RopCatalogoItem::TIPO_SUJETO_OBLIGADO))->searchable()->required(),
            ])->columns(3),
            Forms\Components\Section::make('SECCIÓN II · Información de la operación')->schema([
                Forms\Components\TextInput::make('Monto')->label('7. Monto total')->numeric()->prefix('S/')->disabled(),
                Forms\Components\Select::make('Moneda')->label('8. Tipo de moneda')->options(['PEN' => 'Nuevo Sol', 'USD' => 'US$', 'EUR' => 'Euros', 'OTRO' => 'Otro'])->required(),
                Forms\Components\TextInput::make('DatosRegulatorios.moneda_otro')->label('Otra moneda')->visible(fn (Forms\Get $get): bool => $get('Moneda') === 'OTRO'),
                Forms\Components\TextInput::make('DatosRegulatorios.tipo_cambio')->label('9. Tipo de cambio')->numeric(),
                Forms\Components\CheckboxList::make('DatosRegulatorios.formas_pago')->label('10. Forma de pago/desembolso')->options([
                    'AL_CONTADO' => 'Al contado', 'A_PLAZOS' => 'A plazos', 'EFECTIVO' => 'Efectivo', 'CHEQUE' => 'Cheque', 'GIROS' => 'Giros',
                    'TRANSFERENCIA_BANCARIA' => 'Transferencia bancaria', 'DEPOSITO_CUENTA' => 'Depósito en cuenta', 'TARJETA_CREDITO' => 'Tarjeta de crédito', 'OTROS' => 'Otros',
                ])->columns(3)->columnSpanFull(),
                Forms\Components\TextInput::make('DatosRegulatorios.forma_pago_otro')->label('Especifique otra forma de pago'),
                Forms\Components\CheckboxList::make('DatosRegulatorios.tipos_operacion')->label('11. Tipo de operación')->options($tipoOperacion)->columns(2)->columnSpanFull()->required(),
                Forms\Components\TextInput::make('DatosRegulatorios.tipo_operacion_otro')->label('11. Otro tipo de operación (especifique)'),
                Forms\Components\Select::make('DatosRegulatorios.compra_venta')->label('11.1 Compra o venta')->options(['COMPRA' => 'Compra', 'VENTA' => 'Venta']),
                Forms\Components\TextInput::make('DatosRegulatorios.tipo_acto_contrato')->label('11.2 Tipo de acto o contrato'),
                Forms\Components\TextInput::make('DatosRegulatorios.tipo_servicio')->label('11.3 Tipo de servicio'),
                Forms\Components\Radio::make('DatosRegulatorios.individual_multiple')->label('11.4 Operación')->options(['INDIVIDUAL' => 'Individual', 'MULTIPLE' => 'Múltiple'])->inline()->required(),
                Forms\Components\Select::make('DatosRegulatorios.oportunidad_pago')->label('12. Oportunidad de pago')->options([
                    'FIRMA_CONTRATO' => 'A la firma del contrato/minuta', 'ENTREGA_BIEN' => 'Con la entrega del bien(es)',
                    'AL_MOMENTO_OPERACION' => 'Al momento de la operación', 'PRESTACION_SERVICIO' => 'A la prestación del servicio',
                    'DESPACHO_ADUANERO' => 'Concluido el despacho aduanero', 'FIRMA_ACTA_TRANSFERENCIA' => 'A la firma del acta de transferencia',
                    'FIRMA_INSTRUMENTO_NOTARIAL' => 'A la firma del instrumento público notarial protocolar', 'ENTREGA_MERCADERIA' => 'Con la entrega de la mercadería', 'OTRO' => 'Otros',
                ])->searchable(),
                Forms\Components\TextInput::make('DatosRegulatorios.oportunidad_pago_otro')->label('Otra oportunidad de pago'),
                Forms\Components\Textarea::make('DatosRegulatorios.informacion_adicional')->label('13. Información adicional')->rows(4)->columnSpanFull(),
            ])->columns(3),
            Forms\Components\Section::make('SECCIÓN III · Datos de identificación del cliente')->schema([
                Forms\Components\Radio::make('DatosRegulatorios.tipo_persona')->label('Tipo de persona')->options(['NATURAL' => 'Persona natural', 'JURIDICA' => 'Persona jurídica'])->inline()->live()->required(),
                self::personaNaturalForm(),
                self::personaJuridicaForm(),
            ]),
        ]);
    }

    private static function personaNaturalForm(): Forms\Components\Section
    {
        return Forms\Components\Section::make('III.2 Persona natural')->visible(fn (Forms\Get $get): bool => $get('DatosRegulatorios.tipo_persona') !== 'JURIDICA')->schema([
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.apellido_paterno')->label('24. Apellido paterno')->required(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.apellido_materno')->label('25. Apellido materno')->required(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.nombres')->label('26. Nombres')->required(),
            Forms\Components\DatePicker::make('DatosRegulatorios.persona_natural.fecha_nacimiento')->label('27. Fecha de nacimiento'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.nacionalidad')->label('28. Nacionalidad'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.profesion_ocupacion')->label('29. Profesión/ocupación/actividad o giro'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.tipo_documento')->label('30. Tipo de documento')->required(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.numero_documento')->label('31. N.° de documento')->required(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.pais_emision_documento')->label('32. País de emisión'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.departamento')->label('33.a Departamento'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.provincia')->label('33.b Provincia'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.distrito')->label('33.c Distrito'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.urbanizacion_sector')->label('33.d Urb./AA.HH./Sector/Zona'),
            Forms\Components\Select::make('DatosRegulatorios.persona_natural.tipo_via')->label('33.e Tipo de vía (Tabla N.° 3)')->options(fn (): array => RopCatalogoItem::opciones(RopCatalogoItem::TIPO_VIA))->searchable(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.nombre_via')->label('33.e Nombre de la vía'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.numero_via')->label('33.f Número'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.manzana_lote')->label('33.g Mz./Lote'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.telefono_1')->label('34.a Teléfono 1'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.telefono_2')->label('34.b Teléfono 2'),
            Forms\Components\Radio::make('DatosRegulatorios.persona_natural.estado_civil')->label('35. Estado civil')->options(['SOLTERO' => 'Soltero', 'CASADO' => 'Casado', 'VIUDO' => 'Viudo', 'DIVORCIADO' => 'Divorciado'])->inline(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_natural.conyuge')->label('35.1 Nombres y apellidos del cónyuge'),
        ])->columns(3);
    }

    private static function personaJuridicaForm(): Forms\Components\Section
    {
        return Forms\Components\Section::make('III.1 Persona jurídica')->visible(fn (Forms\Get $get): bool => $get('DatosRegulatorios.tipo_persona') === 'JURIDICA')->schema([
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.razon_social')->label('14. Denominación o razón social')->required(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.ruc')->label('15. RUC')->required(),
            Forms\Components\Select::make('DatosRegulatorios.persona_juridica.actividad_economica')->label('16. Sector/actividad económica (Tabla N.° 2)')->options(fn (): array => RopCatalogoItem::query()->where('Tipo', RopCatalogoItem::ACTIVIDAD_ECONOMICA)->where('Activo', true)->orderBy('Orden')->get()->mapWithKeys(fn ($item) => [$item->Codigo => "{$item->Grupo} — {$item->Nombre}"])->all())->searchable(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.domicilio_extranjero')->label('17. Domicilio legal en el extranjero'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.pais')->label('18. País'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.distrito_estado_extranjero')->label('19. Distrito/Estado'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.departamento')->label('20.a Departamento'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.provincia')->label('20.b Provincia'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.distrito')->label('20.c Distrito'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.urbanizacion_sector')->label('20.d Urb./AA.HH./Sector/Zona'),
            Forms\Components\Select::make('DatosRegulatorios.persona_juridica.tipo_via')->label('20.e Tipo de vía (Tabla N.° 3)')->options(fn (): array => RopCatalogoItem::opciones(RopCatalogoItem::TIPO_VIA))->searchable(),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.nombre_via')->label('20.e Nombre de la vía'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.numero_via')->label('20.f Número'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.manzana_lote')->label('20.g Mz./Lote'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.telefono_1')->label('21.a Teléfono 1'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.telefono_2')->label('21.b Teléfono 2'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.partida_electronica')->label('22.a Partida electrónica'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.sede_registral')->label('22.b Sede registral'),
            Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.registro')->label('22.c Registro'),
            Forms\Components\Fieldset::make('23. Datos del representante legal')->schema([
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.apellido_paterno')->label('Apellido paterno'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.apellido_materno')->label('Apellido materno'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.nombres')->label('Nombres'),
                Forms\Components\DatePicker::make('DatosRegulatorios.persona_juridica.representante.fecha_nacimiento')->label('Fecha de nacimiento'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.nacionalidad')->label('Nacionalidad'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.profesion_ocupacion')->label('Profesión u ocupación'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.estado_civil')->label('Estado civil'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.tipo_documento')->label('Tipo de documento'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.numero_documento')->label('N.° de documento'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.pais_emision')->label('País de emisión'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.departamento')->label('Departamento'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.provincia')->label('Provincia'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.distrito')->label('Distrito'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.urbanizacion_sector')->label('Urb./AA.HH./Sector/Zona'),
                Forms\Components\Select::make('DatosRegulatorios.persona_juridica.representante.tipo_via')->label('Tipo de vía')->options(fn (): array => RopCatalogoItem::opciones(RopCatalogoItem::TIPO_VIA))->searchable(),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.nombre_via')->label('Nombre de la vía'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.numero_via')->label('Número'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.manzana_lote')->label('Mz./Lote'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.telefono_1')->label('Teléfono 1'),
                Forms\Components\TextInput::make('DatosRegulatorios.persona_juridica.representante.telefono_2')->label('Teléfono 2'),
            ])->columns(3)->columnSpanFull(),
        ])->columns(3);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('1. Información del ROP')->schema([
                Infolists\Components\TextEntry::make('NumeroRop')->label('N.° ROP'),
                Infolists\Components\TextEntry::make('FechaOperacion')->label('Fecha')->dateTime('d/m/Y H:i'),
                Infolists\Components\TextEntry::make('Periodo')->date('m/Y'),
                Infolists\Components\TextEntry::make('Estado')->badge()->formatStateUsing(fn ($state) => RopRegistro::estados()[$state] ?? $state),
                Infolists\Components\TextEntry::make('periodo.Estado')->label('Cierre del período')->badge(),
                Infolists\Components\TextEntry::make('UmbralAplicado')->label('Umbral aplicado')->money('PEN'),
            ])->columns(6),
            Infolists\Components\Section::make('2. Cliente')->schema([
                self::snapshot('cliente.NombresApellidos', 'Nombre completo'),
                self::snapshot('cliente.NumeroDocumento', 'Documento'),
                self::snapshot('cliente.FechaNacimiento', 'Fecha de nacimiento'),
                self::snapshot('cliente.Nacionalidad', 'Nacionalidad'),
                self::snapshot('cliente.ProfesionOcupacion', 'Profesión / ocupación'),
                self::snapshot('cliente.ActividadEconomica', 'Actividad económica'),
                self::snapshot('cliente.Domicilio', 'Domicilio')->columnSpanFull(),
                self::snapshot('cliente.Departamento', 'Departamento'),
                self::snapshot('cliente.Provincia', 'Provincia'),
                self::snapshot('cliente.Distrito', 'Distrito'),
                self::snapshot('cliente.Telefono1', 'Teléfono'),
                self::snapshot('cliente.Correo', 'Correo'),
            ])->columns(3)->collapsible(),
            Infolists\Components\Section::make('3. Crédito / préstamo')->schema([
                self::snapshot('credito.CodigoCredito', 'Código'),
                self::snapshot('credito.TipoCredito', 'Tipo'),
                self::snapshot('credito.MontoTotal', 'Monto original')->money('PEN'),
                self::snapshot('credito.MontoTotalPagar', 'Total a pagar')->money('PEN'),
                self::snapshot('credito.NumeroCuotas', 'Cuotas'),
                self::snapshot('credito.MontoCuota', 'Monto de cuota')->money('PEN'),
                self::snapshot('credito.TasaInteres', 'Tasa de interés'),
                self::snapshot('credito.FechaGeneracion', 'Fecha de generación'),
                self::snapshot('credito.FechaVencimiento', 'Fecha de vencimiento'),
            ])->columns(3)->collapsible(),
            Infolists\Components\Section::make('4. Operación')->schema([
                Infolists\Components\TextEntry::make('Monto')->money('PEN'),
                Infolists\Components\TextEntry::make('Moneda'),
                self::snapshot('operacion.TipoOperacionDetalle', 'Tipo de operación'),
                self::snapshot('operacion.IndividualMultiple', 'Alcance'),
                self::snapshot('sede.Nombre', 'Sede'),
                self::snapshot('sede.Zona', 'Zona'),
                self::snapshot('operacion.InformacionAdicional', 'Información adicional')->columnSpanFull(),
            ])->columns(3)->collapsible(),
            Infolists\Components\Section::make('5. Pagos relacionados')->schema([
                Infolists\Components\RepeatableEntry::make('pagos_snapshot')
                    ->hiddenLabel()
                    ->state(fn (RopRegistro $record): array => data_get($record->SnapshotDatos, 'pagos', []))
                    ->schema([
                        Infolists\Components\TextEntry::make('PagoID')->label('Pago'),
                        Infolists\Components\TextEntry::make('FechaPago')->label('Fecha'),
                        Infolists\Components\TextEntry::make('MontoPagado')->label('Monto')->money('PEN'),
                        Infolists\Components\TextEntry::make('TipoPago')->label('Medio'),
                        Infolists\Components\TextEntry::make('Comentario')->placeholder('—'),
                    ])->columns(5),
                Infolists\Components\TextEntry::make('sin_pagos')
                    ->hiddenLabel()->state('No había pagos registrados en JALUD al crear este ROP.')
                    ->visible(fn (RopRegistro $record): bool => empty(data_get($record->SnapshotDatos, 'pagos', []))),
            ])->collapsible(),
            Infolists\Components\Section::make('6. Garantía')->schema([
                self::snapshot('garantia.Tipo', 'Tipo'),
                self::snapshot('garantia.Documento', 'Documento'),
                self::snapshot('garantia.Nombre', 'Garante'),
                self::snapshot('garantia.Domicilio', 'Domicilio')->columnSpanFull(),
                Infolists\Components\TextEntry::make('sin_garantia')->hiddenLabel()->state('JALUD no registra garante para este cliente.')
                    ->visible(fn (RopRegistro $record): bool => empty(data_get($record->SnapshotDatos, 'garantia'))),
            ])->columns(3)->collapsible(),
            Infolists\Components\Section::make('7. Calificación')->schema([
                Infolists\Components\TextEntry::make('Calificacion')->badge()
                    ->state(fn (RopRegistro $record) => $record->Calificacion
                        ?? ($record->Estado === RopRegistro::ESTADO_CALIFICADO ? 'SIN_CLASIFICACION' : null))
                    ->placeholder('Pendiente')
                    ->formatStateUsing(fn ($state) => $state === 'SIN_CLASIFICACION' ? 'Sin clasificación especial' : (RopRegistro::calificaciones()[$state] ?? $state)),
                Infolists\Components\TextEntry::make('calificadoPor.name')->label('Usuario')->placeholder('Pendiente'),
                Infolists\Components\TextEntry::make('CalificadoEn')->label('Fecha y hora')->dateTime('d/m/Y H:i:s')->placeholder('Pendiente'),
                Infolists\Components\TextEntry::make('MotivoCalificacion')->label('Motivo')->columnSpanFull()->placeholder('Pendiente'),
                Infolists\Components\TextEntry::make('ObservacionesCalificacion')->label('Observaciones')->columnSpanFull()->placeholder('—'),
            ])->columns(3),
            Infolists\Components\Section::make('8. Auditoría')->schema([
                Infolists\Components\RepeatableEntry::make('auditoria_detalle')
                    ->hiddenLabel()
                    ->state(fn (RopRegistro $record): array => $record->auditorias()
                        ->with('usuario')
                        ->latest('created_at')
                        ->get()
                        ->map(fn ($auditoria): array => [
                            'created_at' => $auditoria->created_at,
                            'Accion' => $auditoria->Accion,
                            'Usuario' => $auditoria->usuario?->name ?? 'Sistema',
                            'Motivo' => $auditoria->Motivo,
                        ])->all())
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')->label('Fecha y hora')->dateTime('d/m/Y H:i:s'),
                        Infolists\Components\TextEntry::make('Accion')->label('Acción'),
                        Infolists\Components\TextEntry::make('Usuario'),
                        Infolists\Components\TextEntry::make('Motivo')->placeholder('—')->columnSpanFull(),
                    ])->columns(3),
                Infolists\Components\TextEntry::make('sin_auditoria')
                    ->hiddenLabel()
                    ->state('Todavía no hay movimientos de auditoría registrados.')
                    ->visible(fn (RopRegistro $record): bool => ! $record->auditorias()->exists()),
            ])->collapsible(),
        ]);
    }

    private static function snapshot(string $ruta, string $label): Infolists\Components\TextEntry
    {
        return Infolists\Components\TextEntry::make('snapshot_'.str_replace('.', '_', $ruta))
            ->label($label)
            ->state(fn (RopRegistro $record) => data_get($record->SnapshotDatos, $ruta))
            ->placeholder('No disponible');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('FechaOperacion', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('NumeroRop')->label('N.° ROP')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('FechaOperacion')->label('Fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('ClienteNombre')->label('Cliente')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('NumeroDocumento')->label('Documento')->searchable(),
                Tables\Columns\TextColumn::make('CodigoCredito')->label('Crédito')->searchable(),
                Tables\Columns\TextColumn::make('Monto')->money('PEN')->sortable(),
                Tables\Columns\BadgeColumn::make('Estado')->formatStateUsing(fn ($state) => RopRegistro::estados()[$state] ?? $state)
                    ->color(fn ($state) => $state === RopRegistro::ESTADO_CALIFICADO ? 'success' : 'warning'),
                Tables\Columns\BadgeColumn::make('Calificacion')
                    ->getStateUsing(fn (RopRegistro $record) => $record->Calificacion
                        ?? ($record->Estado === RopRegistro::ESTADO_CALIFICADO ? 'SIN_CLASIFICACION' : null))
                    ->placeholder('Pendiente')
                    ->formatStateUsing(fn ($state) => $state === 'SIN_CLASIFICACION' ? 'Sin clasificación especial' : (RopRegistro::calificaciones()[$state] ?? $state))
                    ->color(fn ($state) => match ($state) {
                        RopRegistro::CALIFICACION_SOSPECHOSA => 'danger', RopRegistro::CALIFICACION_INUSUAL => 'warning', 'SIN_CLASIFICACION' => 'success', default => 'gray'
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('Estado')->options(RopRegistro::estados()),
                Tables\Filters\SelectFilter::make('Calificacion')->options(RopRegistro::calificaciones()),
                Tables\Filters\Filter::make('periodo')->label('Período')->form([
                    Forms\Components\DatePicker::make('mes')->label('Mes')->native(false),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['mes'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('Periodo', \Carbon\CarbonImmutable::parse($fecha)->startOfMonth()))),
                Tables\Filters\Filter::make('referencias')->label('Cliente, documento o crédito')->form([
                    Forms\Components\TextInput::make('cliente')->label('Cliente'),
                    Forms\Components\TextInput::make('documento')->label('Documento'),
                    Forms\Components\TextInput::make('credito')->label('Crédito'),
                ])->columns(3)->query(fn (Builder $query, array $data) => $query
                    ->when($data['cliente'] ?? null, fn (Builder $q, $valor) => $q->where('ClienteNombre', 'like', "%{$valor}%"))
                    ->when($data['documento'] ?? null, fn (Builder $q, $valor) => $q->where('NumeroDocumento', 'like', "%{$valor}%"))
                    ->when($data['credito'] ?? null, fn (Builder $q, $valor) => $q->where('CodigoCredito', 'like', "%{$valor}%"))),
                Tables\Filters\Filter::make('fecha')->form([
                    Forms\Components\DatePicker::make('desde'), Forms\Components\DatePicker::make('hasta'),
                ])->columns(2)->query(fn (Builder $query, array $data) => $query
                    ->when($data['desde'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('FechaOperacion', '>=', $fecha))
                    ->when($data['hasta'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('FechaOperacion', '<=', $fecha))),
                Tables\Filters\Filter::make('monto')->form([
                    Forms\Components\TextInput::make('minimo')->numeric(), Forms\Components\TextInput::make('maximo')->numeric(),
                ])->columns(2)->query(fn (Builder $query, array $data) => $query
                    ->when($data['minimo'] ?? null, fn (Builder $q, $monto) => $q->where('Monto', '>=', $monto))
                    ->when($data['maximo'] ?? null, fn (Builder $q, $monto) => $q->where('Monto', '<=', $monto))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver detalle'),
                Tables\Actions\EditAction::make()
                    ->label(fn (RopRegistro $record): string => app(RopCompletenessService::class)->estaCompleto($record)
                        ? 'Editar ROP'
                        : 'Completar ROP')
                    ->icon(fn (RopRegistro $record): string => app(RopCompletenessService::class)->estaCompleto($record)
                        ? 'heroicon-o-pencil-square'
                        : 'heroicon-o-document-plus')
                    ->visible(fn (RopRegistro $record): bool => (auth()->user()?->can('completar_rop') ?? false) && $record->periodo?->Estado !== 'CERRADO'),
                self::revisarCalificarAction(),
                self::generarRosAction(),
                Tables\Actions\Action::make('excel')->label('Excel')->icon('heroicon-o-table-cells')
                    ->visible(fn (RopRegistro $record): bool => app(RopCompletenessService::class)->estaCompleto($record))
                    ->url(fn (RopRegistro $record): string => route('rop.reporte.excel', $record)),
            ])->bulkActions([]);
    }

    public static function generarRosAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generar_ros')->label('Generar ROS')->icon('heroicon-o-shield-exclamation')->color('danger')
            ->visible(fn (RopRegistro $record): bool => (auth()->user()?->can('generar_ros_desde_rop') ?? false)
                && $record->Calificacion === RopRegistro::CALIFICACION_SOSPECHOSA
                && blank($record->RosCasoID))
            ->requiresConfirmation()
            ->action(function (RopRegistro $record) {
                $ros = app(RopToRosService::class)->generar($record);

                return redirect()->to(RosCasoResource::getUrl('edit', ['record' => $ros]));
            });
    }

    public static function revisarCalificarAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('revisar_calificar')
            ->label(fn (RopRegistro $record): string => filled($record->CalificadoEn)
                ? 'Editar calificación'
                : 'Revisar/Calificar')
            ->icon(fn (RopRegistro $record): string => filled($record->CalificadoEn)
                ? 'heroicon-o-pencil-square'
                : 'heroicon-o-clipboard-document-check')
            ->visible(fn (RopRegistro $record): bool => (auth()->user()?->can('calificar_rop') ?? false)
                && app(RopCompletenessService::class)->estaCompleto($record)
                && $record->periodo?->Estado !== 'CERRADO')
            ->form(fn (RopRegistro $record): array => self::calificacionForm($record))
            ->fillForm(fn (RopRegistro $record): array => [
                'ResultadoRevision' => $record->Calificacion ?? 'SIN_CLASIFICACION',
                'MotivoCalificacion' => $record->MotivoCalificacion,
                'ObservacionesCalificacion' => $record->ObservacionesCalificacion,
            ])
            ->action(function (RopRegistro $record, array $data): void {
                app(RopQualificationService::class)->calificar(
                    $record,
                    $data['ResultadoRevision'] === 'SIN_CLASIFICACION' ? null : $data['ResultadoRevision'],
                    $data['MotivoCalificacion'],
                    $data['ObservacionesCalificacion'] ?? null,
                );

                Notification::make()->success()->title('ROP revisado y calificado')->send();
            });
    }

    public static function calificacionForm(RopRegistro $record): array
    {
        return [
            Forms\Components\Placeholder::make('contexto_numero_rop')
                ->label('N.º ROP')
                ->content($record->NumeroRop),
            Forms\Components\Placeholder::make('contexto_cliente')
                ->label('Cliente')
                ->content($record->ClienteNombre),
            Forms\Components\Placeholder::make('contexto_credito')
                ->label('Crédito')
                ->content($record->CodigoCredito),
            Forms\Components\Placeholder::make('contexto_monto')
                ->label('Monto')
                ->content('S/ '.number_format((float) $record->Monto, 2)),
            Forms\Components\Select::make('ResultadoRevision')
                ->label('Resultado de la revisión')
                ->options(['SIN_CLASIFICACION' => 'Sin clasificación especial'] + RopRegistro::calificaciones())
                ->required(),
            Forms\Components\Textarea::make('MotivoCalificacion')
                ->label('Motivo o conclusión de la revisión')
                ->required()
                ->maxLength(5000),
            Forms\Components\Textarea::make('ObservacionesCalificacion')
                ->label('Observaciones')
                ->maxLength(5000),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRopRegistros::route('/'),
            'view' => Pages\ViewRopRegistro::route('/{record}'),
            'edit' => Pages\EditRopRegistro::route('/{record}/completar'),
        ];
    }

    public static function getRelations(): array
    {
        return [RelationManagers\AuditoriasRelationManager::class];
    }
}
