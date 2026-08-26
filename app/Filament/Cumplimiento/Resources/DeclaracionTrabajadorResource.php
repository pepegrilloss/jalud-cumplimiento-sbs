<?php

namespace App\Filament\Cumplimiento\Resources;

use App\Enums\Declaraciones\CodigoOcupacion;
use App\Enums\Declaraciones\EstadoCivil;
use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Enums\Declaraciones\TipoDocumentoIdentidad;
use App\Exceptions\ConsultaDniPeruException;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionTrabajadorResource\RelationManagers;
use App\Filament\Forms\CamposGeograficos;
use App\Models\DeclaracionTrabajador;
use App\Models\Trabajador;
use App\Services\Declaraciones\ConsultaDniPeruService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class DeclaracionTrabajadorResource extends Resource
{
    protected static ?string $model = DeclaracionTrabajador::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Declaraciones juradas';

    protected static ?string $navigationLabel = 'Trabajadores';

    protected static ?string $modelLabel = 'declaración de trabajador';

    protected static ?string $pluralModelLabel = 'Declaraciones de trabajadores';

    protected static ?string $recordTitleAttribute = 'NombreCompleto';

    protected static ?string $slug = 'declaraciones-trabajadores';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope('sede');
    }

    private static function accionGuardarBorrador(): HtmlString
    {
        return new HtmlString(Blade::render('<x-filament::button type="submit" icon="heroicon-o-document-check">Guardar borrador</x-filament::button>'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Datos personales')->icon('heroicon-o-user')->schema(self::datosPersonales()),
                Forms\Components\Wizard\Step::make('Estudios')->icon('heroicon-o-academic-cap')->schema(self::estudios()),
                Forms\Components\Wizard\Step::make('Experiencia laboral')->icon('heroicon-o-briefcase')->schema(self::experiencias()),
                Forms\Components\Wizard\Step::make('Capacitaciones')->icon('heroicon-o-book-open')->schema(self::capacitaciones()),
                Forms\Components\Wizard\Step::make('Antecedentes')->icon('heroicon-o-shield-check')->schema(self::antecedentes()),
                Forms\Components\Wizard\Step::make('Información patrimonial')->icon('heroicon-o-banknotes')->schema(self::patrimonio()),
                Forms\Components\Wizard\Step::make('Revisión')->icon('heroicon-o-clipboard-document-check')->schema([
                    Forms\Components\Section::make('Datos personales')->schema([
                        Forms\Components\Placeholder::make('rev_persona')->label('Trabajador')->content(fn (Forms\Get $get) => trim("{$get('Nombres')} {$get('ApellidoPaterno')} {$get('ApellidoMaterno')}")),
                        Forms\Components\Placeholder::make('rev_documento')->label('Documento')->content(fn (Forms\Get $get) => trim("{$get('TipoDocumento')} {$get('NumeroDocumento')}")),
                        Forms\Components\Placeholder::make('rev_civil')->label('Estado civil')->content(fn (Forms\Get $get) => EstadoCivil::tryFrom((string) $get('EstadoCivil'))?->label() ?? ''),
                        Forms\Components\Placeholder::make('rev_contratacion')->label('Contratación')->content(fn (Forms\Get $get) => (string) $get('FechaContratacion')),
                    ])->columns(4),
                    Forms\Components\Section::make('Formación y antecedentes')->schema([
                        Forms\Components\Placeholder::make('rev_estudios')->label('Estudios')->content(fn (Forms\Get $get) => count($get('estudios') ?? []).' superiores · '.count($get('educacionEscolar') ?? []).' escolares'),
                        Forms\Components\Placeholder::make('rev_experiencias')->label('Experiencia laboral')->content(fn (Forms\Get $get) => count($get('experienciasLaborales') ?? []).' registro(s)'),
                        Forms\Components\Placeholder::make('rev_capacitaciones')->label('Capacitaciones')->content(fn (Forms\Get $get) => count($get('capacitaciones') ?? []).' registro(s)'),
                        Forms\Components\Placeholder::make('rev_antecedentes')->label('Antecedentes')->content(fn (Forms\Get $get) => 'Policiales: '.($get('TieneAntecedentesPoliciales') ? 'Sí' : 'No').' · Penales: '.($get('TieneAntecedentesPenales') ? 'Sí' : 'No')),
                    ])->columns(4),
                    Forms\Components\Section::make('Resumen patrimonial')->schema([
                        Forms\Components\Placeholder::make('rev_ingresos')->label('Ingresos totales')->content(fn (Forms\Get $get) => 'S/ '.number_format((float) $get('RemuneracionBrutaMensual') + (float) $get('IngresoEjercicioIndividual') + (float) $get('OtrosIngresosMensuales'), 2)),
                        Forms\Components\Placeholder::make('rev_bienes')->label('Bienes')->content(fn (Forms\Get $get) => count($get('bienesInmuebles') ?? []).' inmueble(s) · '.count($get('vehiculos') ?? []).' vehículo(s)'),
                        Forms\Components\Placeholder::make('rev_inversion')->label('Inversiones')->content(fn (Forms\Get $get) => 'S/ '.number_format(collect($get('inversiones') ?? [])->sum(fn ($r) => (float) ($r['Valor'] ?? 0)), 2)),
                        Forms\Components\Placeholder::make('rev_deuda')->label('Deuda total')->content(fn (Forms\Get $get) => 'S/ '.number_format(collect($get('obligaciones') ?? [])->sum(fn ($r) => (float) ($r['Monto'] ?? 0)), 2)),
                    ])->columns(4),
                    Forms\Components\TextInput::make('CiudadSuscripcion')->label('Ciudad de suscripción')->required(),
                    Forms\Components\DatePicker::make('FechaDeclaracion')->label('Fecha de declaración')->required()->default(now()),
                ])->columns(2),
                Forms\Components\Wizard\Step::make('Documentos')->icon('heroicon-o-document-arrow-down')->schema([
                    Forms\Components\Placeholder::make('documentos_info')->hiddenLabel()->content('Después de guardar y finalizar la declaración se habilitará la generación de los Anexos 1, 2 y 3 desde la pantalla de detalle.'),
                ]),
            ])->columnSpanFull()->skippable(false)
                ->submitAction(in_array($form->getOperation(), ['create', 'edit'], true) ? self::accionGuardarBorrador() : null),
        ]);
    }

    private static function datosPersonales(): array
    {
        return [
            Forms\Components\Section::make('Trabajador y contratación')->schema([
                Forms\Components\Select::make('TrabajadorID')->label('Trabajador')
                    ->relationship(
                        name: 'trabajador',
                        titleAttribute: 'Nombres',
                        modifyQueryUsing: fn ($query) => $query->withoutGlobalScopes()->where('Activo', true),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Trabajador $record): string => "{$record->NombreCompleto} · {$record->NumeroDocumento}")
                    ->getOptionLabelUsing(function ($value): ?string {
                        $record = Trabajador::withoutGlobalScopes()->find($value);

                        return $record
                            ? "{$record->NombreCompleto} · {$record->NumeroDocumento}"
                            : null;
                    })
                    ->searchable(['Nombres', 'ApellidoPaterno', 'ApellidoMaterno', 'NumeroDocumento'])
                    ->preload()
                    ->required()->live()->disabledOn('edit')
                    ->createOptionForm([
                        Forms\Components\Select::make('TipoDocumento')->options(TipoDocumentoIdentidad::opciones())->default('DNI')->required()->live(),
                        Forms\Components\TextInput::make('NumeroDocumento')->label('Número de documento')->required()->maxLength(30)
                            ->rules(fn (Forms\Get $get): array => $get('TipoDocumento') === 'DNI' ? ['digits:8'] : [])
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('consultarDni')
                                    ->label('Consultar DNI')
                                    ->tooltip('Buscar nombres y apellidos por DNI')
                                    ->icon('heroicon-m-magnifying-glass')
                                    ->visible(fn (Forms\Get $get): bool => $get('TipoDocumento') === 'DNI')
                                    ->action(function (Forms\Get $get, Forms\Set $set): void {
                                        $dni = trim((string) $get('NumeroDocumento'));

                                        if (preg_match('/^\d{8}$/', $dni) !== 1) {
                                            Notification::make()->danger()->title('DNI inválido')->body('Ingrese los 8 dígitos antes de consultar.')->send();

                                            return;
                                        }

                                        try {
                                            $persona = app(ConsultaDniPeruService::class)->consultar($dni);
                                        } catch (ConsultaDniPeruException $exception) {
                                            Notification::make()->danger()->title('No se pudo consultar el DNI')->body($exception->getMessage())->send();

                                            return;
                                        }

                                        if (! $persona) {
                                            Notification::make()->warning()->title('DNI no encontrado')->body('No fue posible obtener los datos. Puede completar los nombres manualmente.')->send();

                                            return;
                                        }

                                        $set('Nombres', $persona['nombres']);
                                        $set('ApellidoPaterno', $persona['apellidoPaterno']);
                                        $set('ApellidoMaterno', $persona['apellidoMaterno']);
                                        Notification::make()->success()->title('Datos encontrados')->body('Se cargaron los nombres y apellidos del trabajador.')->send();
                                    }),
                            ),
                        Forms\Components\TextInput::make('Nombres')->required()->maxLength(150),
                        Forms\Components\TextInput::make('ApellidoPaterno')->required()->maxLength(100),
                        Forms\Components\TextInput::make('ApellidoMaterno')->maxLength(100),
                    ])->createOptionUsing(function (array $data) {
                        $sedeId = session('sede_activa') ?: auth()->user()?->SedeID;

                        $data['Activo'] = true;

                        if ($sedeId) {
                            $data['SedeID'] = $sedeId;
                        }

                        return Trabajador::withoutGlobalScopes()->create($data)->getKey();
                    })
                    ->afterStateUpdated(function ($state, Forms\Set $set): void {
                        $t = Trabajador::withoutGlobalScopes()->find($state);
                        if (! $t) {
                            return;
                        }
                        foreach (['Nombres', 'ApellidoPaterno', 'ApellidoMaterno', 'TipoDocumento', 'NumeroDocumento'] as $campo) {
                            $set($campo, $t->{$campo});
                        }
                    }),
                Forms\Components\DatePicker::make('FechaContratacion')->label('Fecha de contratación')->required()->maxDate(now()),
            ])->columns(2),
            Forms\Components\Section::make('Identificación')->schema([
                Forms\Components\TextInput::make('Nombres')->required()->maxLength(150),
                Forms\Components\TextInput::make('ApellidoPaterno')->label('Apellido paterno')->required()->maxLength(100),
                Forms\Components\TextInput::make('ApellidoMaterno')->label('Apellido materno')->maxLength(100),
                Forms\Components\Select::make('TipoDocumento')->label('Tipo de documento')->options(TipoDocumentoIdentidad::opciones())->required()->live()->afterStateUpdated(fn ($state, Forms\Set $set) => $state !== 'OTRO' ? $set('TipoDocumentoOtro', null) : null),
                Forms\Components\TextInput::make('TipoDocumentoOtro')->label('Especifique tipo de documento')->required(fn (Forms\Get $get) => $get('TipoDocumento') === 'OTRO')->visible(fn (Forms\Get $get) => $get('TipoDocumento') === 'OTRO')->dehydratedWhenHidden(false),
                Forms\Components\TextInput::make('NumeroDocumento')->label('Número de documento')->required()->maxLength(30)->rules(fn (Forms\Get $get) => [new \App\Rules\DocumentoIdentidadValido($get('TipoDocumento'))]),
                CamposGeograficos::pais('Nacionalidad', 'País de nacionalidad'),
                CamposGeograficos::pais('Pais', 'País, cuando corresponda'),
                Forms\Components\TextInput::make('LugarNacimiento')->label('Lugar de nacimiento')->maxLength(180),
                Forms\Components\DatePicker::make('FechaNacimiento')->label('Fecha de nacimiento')->maxDate(now()),
                Forms\Components\Select::make('EstadoCivil')->label('Estado civil')->options(EstadoCivil::opciones())->required()->live()->afterStateUpdated(function ($state, Forms\Set $set) {
                    if (! in_array($state, ['CASADO', 'CONVIVIENTE'], true)) {
                        $set('ConyugeNombres', null);
                        $set('ConyugeApellidos', null);
                    }
                }),
                Forms\Components\TextInput::make('ConyugeNombres')->label('Nombres del cónyuge/conviviente')->required(fn (Forms\Get $get) => in_array($get('EstadoCivil'), ['CASADO', 'CONVIVIENTE'], true))->visible(fn (Forms\Get $get) => in_array($get('EstadoCivil'), ['CASADO', 'CONVIVIENTE'], true))->dehydratedWhenHidden(false),
                Forms\Components\TextInput::make('ConyugeApellidos')->label('Apellidos del cónyuge/conviviente')->required(fn (Forms\Get $get) => in_array($get('EstadoCivil'), ['CASADO', 'CONVIVIENTE'], true))->visible(fn (Forms\Get $get) => in_array($get('EstadoCivil'), ['CASADO', 'CONVIVIENTE'], true))->dehydratedWhenHidden(false),
            ])->columns(3),
            Forms\Components\Section::make('Actividad y contacto')->schema([
                Forms\Components\TextInput::make('ProfesionOficio')->label('Profesión u oficio')->maxLength(180),
                Forms\Components\Select::make('CodigoOcupacion')->label('Ocupación')->options(CodigoOcupacion::opciones())->required()->searchable(),
                Forms\Components\TextInput::make('Cargo')->label('Cargo en el sujeto obligado')->required()->maxLength(180),
                Forms\Components\TextInput::make('Telefono')->label('Teléfono personal')->tel()->maxLength(50),
            ])->columns(2),
            Forms\Components\Section::make('Domicilio personal')->schema(self::domicilio())->columns(4),
        ];
    }

    private static function domicilio(): array
    {
        return [
            Forms\Components\TextInput::make('DomicilioVia')->label('Jr. / Av. / Calle / Pasaje')->required()->columnSpan(2),
            Forms\Components\TextInput::make('DomicilioNumero')->label('Número'),
            Forms\Components\TextInput::make('DomicilioInterior')->label('Dpto. / Interior'),
            Forms\Components\TextInput::make('DomicilioZona')->label('Urb. / Complejo / Zona / Sector')->columnSpan(2),
            ...CamposGeograficos::domicilio('DomicilioDepartamento', 'DomicilioProvincia', 'DomicilioDistrito'),
        ];
    }

    private static function estudios(): array
    {
        return [
            Forms\Components\Repeater::make('estudios')->relationship()->label('Postgrado y pregrado')->schema([
                Forms\Components\Select::make('Nivel')->options(['DOCTORADO' => 'Doctorado', 'MAESTRIA' => 'Maestría', 'DIPLOMADO' => 'Diplomado o Especialización', 'LICENCIATURA' => 'Licenciatura o Título Profesional', 'BACHILLERATO' => 'Bachillerato', 'TECNICO' => 'Estudios Técnicos'])->required(),
                Forms\Components\TextInput::make('TituloGrado')->label('Título o grado'), Forms\Components\TextInput::make('Especialidad'),
                Forms\Components\TextInput::make('CentroEstudios')->label('Universidad / Instituto / Centro'),
                Forms\Components\TextInput::make('Ciudad'), CamposGeograficos::pais('Pais'),
                Forms\Components\DatePicker::make('FechaExpedicion')->label('Fecha de expedición'),
                Forms\Components\Select::make('EstadoEstudio')->label('Estado')->options(['TITULADO' => 'Titulado', 'EN_TRAMITE' => 'Título en trámite', 'EGRESADO' => 'Egresado', 'ESTUDIANDO' => 'Actualmente estudiando'])->live(),
                Forms\Components\TextInput::make('CicloActual')->label('Ciclo actual')->numeric()->visible(fn (Forms\Get $get) => $get('EstadoEstudio') === 'ESTUDIANDO'),
            ])->columns(3)->addActionLabel('Agregar estudio')->reorderableWithButtons()->orderColumn('Orden')->defaultItems(0),
            Forms\Components\Repeater::make('educacionEscolar')->relationship()->label('Educación escolar')->schema([
                Forms\Components\Select::make('Nivel')->options(['PRIMARIA' => 'Primaria', 'SECUNDARIA' => 'Secundaria'])->required(),
                Forms\Components\Toggle::make('Concluida')->label('Concluida')->live(),
                Forms\Components\TextInput::make('UltimoGrado')->label('Último grado/año')->visible(fn (Forms\Get $get) => ! $get('Concluida')),
                Forms\Components\TextInput::make('Colegio'), Forms\Components\TextInput::make('Ciudad'), CamposGeograficos::pais('Pais'),
                Forms\Components\TextInput::make('AnioInicio')->label('Año inicio')->numeric()->minValue(1900)->maxValue((int) date('Y')),
                Forms\Components\TextInput::make('AnioTermino')->label('Año término')->numeric()->minValue(1900)->maxValue((int) date('Y')),
            ])->columns(4)->maxItems(2)->addActionLabel('Agregar nivel escolar')->orderColumn('Orden')->defaultItems(0),
            Forms\Components\Repeater::make('otrosEstudios')->relationship()->label('Otros estudios')->schema([
                Forms\Components\Select::make('Tipo')->options(['IDIOMA' => 'Idioma', 'COMPUTACION' => 'Computación', 'OTRO' => 'Otro'])->required(),
                Forms\Components\TextInput::make('Descripcion')->label('Descripción')->required(),
                Forms\Components\TextInput::make('Institucion')->label('Institución'),
            ])->columns(3)->addActionLabel('Agregar otro estudio')->orderColumn('Orden')->defaultItems(0),
        ];
    }

    private static function experiencias(): array
    {
        return [Forms\Components\Repeater::make('experienciasLaborales')->relationship()->label('Experiencias laborales')->schema([
            Forms\Components\TextInput::make('EmpresaEntidad')->label('Empresa, entidad o persona natural')->required(),
            Forms\Components\TextInput::make('CargoDesempenado')->label('Cargo desempeñado')->required(),
            Forms\Components\DatePicker::make('FechaInicio')->label('Fecha de inicio')->required(),
            Forms\Components\DatePicker::make('FechaConclusion')->label('Fecha de conclusión')->afterOrEqual('FechaInicio')->helperText('Déjelo vacío si es el trabajo actual.'),
        ])->columns(2)->addActionLabel('Agregar experiencia laboral')->orderColumn('Orden')->defaultItems(0)];
    }

    private static function capacitaciones(): array
    {
        return [Forms\Components\Repeater::make('capacitaciones')->relationship()->label('Capacitaciones recibidas')->schema([
            Forms\Components\TextInput::make('NombreCursoEvento')->label('Curso o evento')->required(),
            Forms\Components\TextInput::make('InstitucionOrganizadora')->label('Institución organizadora')->required(),
            Forms\Components\DatePicker::make('FechaCapacitacion')->label('Mes / año')->required()->displayFormat('m/Y'),
        ])->columns(3)->addActionLabel('Agregar capacitación')->orderColumn('Orden')->defaultItems(0)];
    }

    private static function antecedentes(): array
    {
        return [Forms\Components\Section::make('Antecedentes policiales y penales')->schema([
            Forms\Components\Toggle::make('TieneAntecedentesPoliciales')->label('¿Tiene antecedentes policiales?')->live()->afterStateUpdated(fn ($state, Forms\Set $set) => ! $state ? $set('DetalleAntecedentesPoliciales', null) : null),
            Forms\Components\Textarea::make('DetalleAntecedentesPoliciales')->label('Razón / detalle')->required(fn (Forms\Get $get) => (bool) $get('TieneAntecedentesPoliciales'))->visible(fn (Forms\Get $get) => (bool) $get('TieneAntecedentesPoliciales'))->dehydratedWhenHidden(false),
            Forms\Components\Toggle::make('TieneAntecedentesPenales')->label('¿Tiene antecedentes penales?')->live()->afterStateUpdated(fn ($state, Forms\Set $set) => ! $state ? $set('DetalleAntecedentesPenales', null) : null),
            Forms\Components\Textarea::make('DetalleAntecedentesPenales')->label('Razón / detalle')->required(fn (Forms\Get $get) => (bool) $get('TieneAntecedentesPenales'))->visible(fn (Forms\Get $get) => (bool) $get('TieneAntecedentesPenales'))->dehydratedWhenHidden(false),
        ])->columns(2)];
    }

    private static function patrimonio(): array
    {
        $monto = fn (string $campo, string $label) => Forms\Components\TextInput::make($campo)->label($label)->numeric()->prefix('S/')->default(0)->minValue(0)->live(debounce: 500);

        return [
            Forms\Components\Section::make('Información personal patrimonial')->schema([
                Forms\Components\TextInput::make('NumeroDependientes')->label('Número de dependientes')->numeric()->default(0)->minValue(0),
                Forms\Components\Select::make('CondicionVivienda')->label('Condición del inmueble')->options(['PROPIA' => 'Casa propia', 'PADRES' => 'De los padres', 'SOCIEDAD_CONYUGAL' => 'De la sociedad conyugal', 'CONCUBINOS' => 'De los concubinos', 'ALQUILADA' => 'Alquilada', 'CEDIDA' => 'Cedida en uso', 'OTRA' => 'Otra'])->live()->afterStateUpdated(fn ($state, Forms\Set $set) => $state !== 'OTRA' ? $set('CondicionViviendaOtra', null) : null),
                Forms\Components\TextInput::make('CondicionViviendaOtra')->label('Especifique')->required(fn (Forms\Get $get) => $get('CondicionVivienda') === 'OTRA')->visible(fn (Forms\Get $get) => $get('CondicionVivienda') === 'OTRA')->dehydratedWhenHidden(false),
            ])->columns(3),
            Forms\Components\Section::make('I. Ingresos')->schema([
                $monto('RemuneracionBrutaMensual', 'Remuneración bruta mensual'), $monto('IngresoEjercicioIndividual', 'Ingresos por ejercicio individual'), $monto('OtrosIngresosMensuales', 'Otros ingresos mensuales'),
                Forms\Components\Placeholder::make('TotalIngresosCalculado')->label('Total ingresos')->content(fn (Forms\Get $get) => 'S/ '.number_format((float) $get('RemuneracionBrutaMensual') + (float) $get('IngresoEjercicioIndividual') + (float) $get('OtrosIngresosMensuales'), 2)),
                Forms\Components\Textarea::make('DetalleOtrosIngresos')->label('Detalle de otros ingresos')->columnSpanFull(),
            ])->columns(3),
            Forms\Components\Repeater::make('bienesInmuebles')->relationship()->label('II. Bienes inmuebles')->schema([
                Forms\Components\Select::make('ClasificacionPropiedad')->label('Clasificación')->options(['P' => 'P - Bien propio', 'C' => 'C - Sociedad conyugal / convivientes', 'CO' => 'CO - Copropietario con terceros'])->required(),
                Forms\Components\TextInput::make('Direccion')->label('Dirección')->required()->columnSpan(2),
                Forms\Components\TextInput::make('FichaPartidaRegistral')->label('Ficha / partida registral'), $monto('Valor', 'Valor'),
            ])->columns(3)->addActionLabel('Agregar inmueble')->orderColumn('Orden')->defaultItems(0),
            Forms\Components\Placeholder::make('TotalInmueblesCalculado')->label('Total inmuebles')->content(fn (Forms\Get $get) => 'S/ '.number_format(collect($get('bienesInmuebles') ?? [])->sum(fn ($r) => (float) ($r['Valor'] ?? 0)), 2)),
            Forms\Components\Repeater::make('vehiculos')->relationship()->label('III. Vehículos')->schema([
                Forms\Components\TextInput::make('TipoVehiculo')->label('Tipo')->required(), Forms\Components\TextInput::make('Marca'), Forms\Components\TextInput::make('Modelo'),
                Forms\Components\TextInput::make('Anio')->label('Año')->numeric(), Forms\Components\TextInput::make('Placa'), $monto('Valor', 'Valor'),
            ])->columns(3)->addActionLabel('Agregar vehículo')->orderColumn('Orden')->defaultItems(0),
            Forms\Components\Repeater::make('otrosBienes')->relationship()->label('Otros bienes muebles')->schema([
                Forms\Components\TextInput::make('Descripcion')->label('Descripción')->required(), Forms\Components\TextInput::make('Caracteristicas')->label('Características'),
                $monto('Valor', 'Valor'), Forms\Components\Toggle::make('EsRegistrable')->label('Bien registrable'),
            ])->columns(2)->addActionLabel('Agregar otro bien')->orderColumn('Orden')->defaultItems(0),
            Forms\Components\Placeholder::make('TotalBienesMueblesCalculado')->label('Total bienes muebles')->content(fn (Forms\Get $get) => 'S/ '.number_format(collect($get('vehiculos') ?? [])->sum(fn ($r) => (float) ($r['Valor'] ?? 0)) + collect($get('otrosBienes') ?? [])->sum(fn ($r) => (float) ($r['Valor'] ?? 0)), 2)),
            Forms\Components\Repeater::make('inversiones')->relationship()->label('IV. Ahorros, depósitos e inversiones')->schema([
                Forms\Components\TextInput::make('EntidadFinanciera')->label('Entidad financiera')->required(),
                Forms\Components\TextInput::make('TipoInstrumento')->label('Tipo de cuenta, depósito o instrumento')->required(), $monto('Valor', 'Valor'),
            ])->columns(3)->addActionLabel('Agregar ahorro o inversión')->orderColumn('Orden')->defaultItems(0),
            Forms\Components\Placeholder::make('TotalInversionesCalculado')->label('Total inversiones')->content(fn (Forms\Get $get) => 'S/ '.number_format(collect($get('inversiones') ?? [])->sum(fn ($r) => (float) ($r['Valor'] ?? 0)), 2)),
            Forms\Components\Repeater::make('obligaciones')->relationship()->label('Acreencias y obligaciones')->schema([
                Forms\Components\TextInput::make('Detalle')->label('Detalle')->required(),
                Forms\Components\Select::make('TipoAcreedor')->label('Asumida ante')->options(['ENTIDAD_FINANCIERA' => 'Entidad financiera', 'PERSONA_NATURAL' => 'Persona natural', 'OTRO' => 'Otro'])->required()->live(),
                Forms\Components\TextInput::make('AcreedorOtro')->label('Especifique')->visible(fn (Forms\Get $get) => $get('TipoAcreedor') === 'OTRO'), $monto('Monto', 'Monto'),
            ])->columns(2)->addActionLabel('Agregar obligación')->orderColumn('Orden')->defaultItems(0),
            Forms\Components\Placeholder::make('TotalDeudaCalculado')->label('Total deuda')->content(fn (Forms\Get $get) => 'S/ '.number_format(collect($get('obligaciones') ?? [])->sum(fn ($r) => (float) ($r['Monto'] ?? 0)), 2)),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::prepararListado($query))
            ->defaultSort('created_at', 'desc')->columns([
                Tables\Columns\TextColumn::make('NombreCompleto')->label('Trabajador')->searchable(['Nombres', 'ApellidoPaterno', 'ApellidoMaterno']),
                Tables\Columns\TextColumn::make('NumeroDocumento')->label('Documento')->searchable(),
                Tables\Columns\TextColumn::make('versiones_count')->label('Versiones')->badge(),
                Tables\Columns\TextColumn::make('FechaDeclaracion')->label('Fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('Estado')->badge()->formatStateUsing(fn ($state) => EstadoDeclaracion::tryFrom($state)?->label() ?? $state)->color(fn ($state) => $state === 'FINALIZADO' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('situacion_laboral')->label('Situación')->state(fn ($record): string => $record->trabajador?->Activo ? 'Activo' : 'De baja')->badge()->color(fn (string $state): string => $state === 'Activo' ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('documentos_1')->label('Anexo 1')->state(fn ($record) => $record->documentos()->where('TipoDocumento', 'ANEXO_1')->exists())->boolean(),
                Tables\Columns\IconColumn::make('documentos_2')->label('Anexo 2')->state(fn ($record) => $record->documentos()->where('TipoDocumento', 'ANEXO_2')->exists())->boolean(),
                Tables\Columns\IconColumn::make('documentos_3')->label('Anexo 3')->state(fn ($record) => $record->documentos()->where('TipoDocumento', 'ANEXO_3')->exists())->boolean(),
            ])->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn ($record) => $record->Estado === 'BORRADOR'),
                Tables\Actions\Action::make('dar_de_baja')
                    ->label('Dar de baja')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => (bool) $record->trabajador?->Activo && (auth()->user()?->hasRole('super_admin') || auth()->user()?->can('editar_declaraciones_trabajador')))
                    ->action(function ($record): void {
                        $record->trabajador?->update(['Activo' => false]);
                        Notification::make()->success()->title('Trabajador marcado como de baja')->send();
                    }),
                Tables\Actions\Action::make('reactivar')
                    ->label('Reactivar')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => ! (bool) $record->trabajador?->Activo && (auth()->user()?->hasRole('super_admin') || auth()->user()?->can('editar_declaraciones_trabajador')))
                    ->action(function ($record): void {
                        $record->trabajador?->update(['Activo' => true]);
                        Notification::make()->success()->title('Trabajador reactivado')->send();
                    }),
            ])->bulkActions([]);
    }

    public static function soloUltimaVersion(Builder $query): Builder
    {
        return $query->whereIn(
            'DeclaracionTrabajadorID',
            DeclaracionTrabajador::query()
                ->withoutGlobalScope('sede')
                ->selectRaw('MAX(DeclaracionTrabajadorID)')
                ->groupBy('TrabajadorID'),
        );
    }

    public static function prepararListado(Builder $query): Builder
    {
        return self::soloUltimaVersion($query)
            ->with(['trabajador' => fn ($trabajadores) => $trabajadores->withoutGlobalScopes()])
            ->withCount('versiones');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VersionesRelationManager::class,
            RelationManagers\ArchivosAdjuntosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDeclaracionesTrabajador::route('/'), 'create' => Pages\CreateDeclaracionTrabajador::route('/create'), 'view' => Pages\ViewDeclaracionTrabajador::route('/{record}'), 'edit' => Pages\EditDeclaracionTrabajador::route('/{record}/edit')];
    }
}
