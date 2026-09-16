<?php

namespace App\Filament\Cumplimiento\Resources;

use App\Filament\Cumplimiento\Resources\RosCasoResource\Pages;
use App\Filament\Cumplimiento\Resources\RosCasoResource\RelationManagers;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Pago;
use App\Models\RosCaso;
use App\Models\RosCatalogoItem;
use App\Models\Sede;
use App\Models\Trabajador;
use App\Models\Zona;
use App\Services\Ros\RosPrefillService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class RosCasoResource extends Resource
{
    protected static ?string $model = RosCaso::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Cumplimiento';

    protected static ?string $navigationLabel = 'Casos ROS';

    protected static ?string $modelLabel = 'Caso ROS';

    protected static ?string $pluralModelLabel = 'Casos ROS';

    protected static ?string $recordTitleAttribute = 'CodigoInterno';

    protected static ?int $navigationSort = 3;

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
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Sección I')->icon('heroicon-o-clipboard-document-list')->schema(self::pasoCaso()),
                Forms\Components\Wizard\Step::make('Sección II')->icon('heroicon-o-building-office')->schema(self::pasoEmpresa()),
                Forms\Components\Wizard\Step::make('Sección III')->icon('heroicon-o-user-group')->schema(self::pasoPersonas()),
                Forms\Components\Wizard\Step::make('Sección IV')->icon('heroicon-o-banknotes')->schema(self::pasoOperaciones()),
                Forms\Components\Wizard\Step::make('Secciones V y VI')->icon('heroicon-o-magnifying-glass')->schema(self::pasoAnalisis()),
                Forms\Components\Wizard\Step::make('Control interno')->icon('heroicon-o-check-badge')->schema(self::pasoCierre()),
            ])->columnSpanFull()->disabled(fn (?RosCaso $record): bool => $record?->Bloqueado ?? false),
        ]);
    }

    private static function pasoCaso(): array
    {
        return [
            Forms\Components\Section::make('Uso interno del sistema')
                ->description('Estos datos sirven para precargar y auditar el caso. No se exportan al formato ROS.')
                ->schema([
                    Forms\Components\Hidden::make('RopRegistroID'),
                    Forms\Components\Hidden::make('OrigenRos')->default(RosCaso::ORIGEN_DIRECTO),
                    Forms\Components\Placeholder::make('origen_ros_visible')
                        ->label('Origen del ROS')
                        ->content(function (Get $get, ?RosCaso $record): string {
                            if ($record?->OrigenRos === RosCaso::ORIGEN_ROP) {
                                return 'Generado desde ROP'.($record->registroRop?->NumeroRop ? " {$record->registroRop->NumeroRop}" : '');
                            }

                            return $get('TipoPersonaOrigen') === RosCaso::PERSONA_TRABAJADOR
                                ? 'Directo desde Declaraciones juradas'
                                : 'Directo desde JALUD';
                        }),
                    Forms\Components\TextInput::make('CodigoInterno')->label('Código interno')->disabled()->dehydrated(false)->visible(fn (?RosCaso $record): bool => $record !== null),
                    Forms\Components\Placeholder::make('estado_actual')->label('Estado')->content(fn (?RosCaso $record): string => $record ? (RosCaso::estados()[$record->Estado] ?? $record->Estado) : 'Borrador'),
                    Forms\Components\Hidden::make('Estado')->default(RosCaso::ESTADO_BORRADOR),
                    Forms\Components\Select::make('SedeID')
                        ->label('Sede')
                        ->options(fn () => Sede::query()->operativas()->whereKey(auth()->user()?->getEffectiveSedeId())->pluck('Nombre', 'SedeID'))
                        ->default(fn () => auth()->user()?->getEffectiveSedeId())
                        ->required()->disabled()->dehydrated()->live()
                        ->afterStateUpdated(fn ($state, Set $set) => self::precargarConfiguracion($state, $set))
                        ->visible(fn (): bool => auth()->user()?->can('ver_todos_los_casos_sbs') ?? false),
                    Forms\Components\Placeholder::make('sede_asignada')
                        ->label('Sede')
                        ->content(fn (): string => auth()->user()?->sede?->Nombre ?? 'Sede asignada al usuario')
                        ->visible(fn (): bool => ! (auth()->user()?->can('ver_todos_los_casos_sbs') ?? false)),
                    Forms\Components\Select::make('TipoPersonaOrigen')
                        ->label('¿A quién corresponde el caso ROS?')
                        ->options([
                            RosCaso::PERSONA_CLIENTE => 'Cliente',
                            RosCaso::PERSONA_TRABAJADOR => 'Trabajador',
                        ])
                        ->default(RosCaso::PERSONA_CLIENTE)
                        ->required()
                        ->live()
                        ->disabled(fn (?RosCaso $record): bool => $record?->OrigenRos === RosCaso::ORIGEN_ROP)
                        ->dehydrated()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $set('ClienteID', null);
                            $set('TrabajadorID', null);
                            $set('CreditoID', null);
                            $set('PagoID', null);
                            $set('ZonaID', null);
                            $set('personas', []);
                            $set('operaciones', []);
                        }),
                    Forms\Components\Select::make('ZonaID')->label('Zona')
                        ->options(fn (): array => Zona::query()->where('Activo', true)->orderBy('Nombre')->pluck('Nombre', 'ZonaID')->all())
                        ->searchable()->preload()
                        ->visible(fn (Get $get): bool => $get('TipoPersonaOrigen') !== RosCaso::PERSONA_TRABAJADOR),
                    Forms\Components\Select::make('ClienteID')
                        ->label('Cliente para precarga')
                        ->options(fn (): array => Cliente::query()->where('Activo', true)->orderBy('NombresApellidos')->limit(50)->get()
                            ->mapWithKeys(fn (Cliente $cliente): array => [$cliente->ClienteID => self::etiquetaClienteJalud($cliente)])->all())
                        ->getSearchResultsUsing(fn (string $search): array => Cliente::query()->where('Activo', true)
                            ->where(fn (Builder $query) => $query->where('DNI', 'like', "%{$search}%")->orWhere('NombresApellidos', 'like', "%{$search}%"))
                            ->orderBy('NombresApellidos')->limit(50)->get()
                            ->mapWithKeys(fn (Cliente $cliente): array => [$cliente->ClienteID => self::etiquetaClienteJalud($cliente)])->all())
                        ->getOptionLabelUsing(fn ($value): ?string => ($cliente = Cliente::query()->find($value)) ? self::etiquetaClienteJalud($cliente) : null)
                        ->preload()->live()
                        ->visible(fn (Get $get): bool => $get('TipoPersonaOrigen') !== RosCaso::PERSONA_TRABAJADOR)
                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                            if ($state && blank($get('personas'))) {
                                $set('personas', app(RosPrefillService::class)->personasParaCliente((int) $state));
                            }
                            $set('CreditoID', null);
                            $set('PagoID', null);
                            $set('operaciones', []);
                        }),
                    Forms\Components\Select::make('CreditoID')
                        ->label('Crédito para trazabilidad')
                        ->options(fn (Get $get): array => self::creditosJalud($get('ClienteID') ? (int) $get('ClienteID') : null))
                        ->getSearchResultsUsing(fn (string $search, Get $get): array => self::creditosJalud(
                            $get('ClienteID') ? (int) $get('ClienteID') : null,
                            $search,
                        ))
                        ->getOptionLabelUsing(fn ($value): ?string => ($credito = Credito::query()->with('proposicion')->find($value))
                            ? self::etiquetaCreditoJalud($credito)
                            : null)
                        ->searchable()->preload()->live()
                        ->visible(fn (Get $get): bool => $get('TipoPersonaOrigen') !== RosCaso::PERSONA_TRABAJADOR)
                        ->helperText('Puede buscar el crédito directamente o filtrar primero por cliente.')
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $set('PagoID', null);
                            if ($state) {
                                self::aplicarPrecargaOperacion((int) $state, null, $set);
                            }
                        }),
                    Forms\Components\Select::make('PagoID')
                        ->label('Pago para trazabilidad')
                        ->options(fn (Get $get): array => $get('CreditoID') ? self::pagosJalud((int) $get('CreditoID')) : [])
                        ->getSearchResultsUsing(fn (string $search, Get $get): array => $get('CreditoID')
                            ? self::pagosJalud((int) $get('CreditoID'), $search)
                            : [])
                        ->getOptionLabelUsing(fn ($value): ?string => ($pago = Pago::query()->find($value))
                            ? self::etiquetaPagoJalud($pago)
                            : null)
                        ->searchable()->preload()->live()
                        ->visible(fn (Get $get): bool => $get('TipoPersonaOrigen') !== RosCaso::PERSONA_TRABAJADOR)
                        ->disabled(fn (Get $get): bool => blank($get('CreditoID')))
                        ->helperText(fn (Get $get): ?string => blank($get('CreditoID')) ? 'Primero seleccione el crédito.' : null)
                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                            if ($get('CreditoID')) {
                                self::aplicarPrecargaOperacion((int) $get('CreditoID'), $state ? (int) $state : null, $set);
                            }
                        }),
                    Forms\Components\Select::make('TrabajadorID')
                        ->label('Trabajador para precarga')
                        ->options(fn (): array => self::trabajadoresDeclaraciones())
                        ->getSearchResultsUsing(fn (string $search): array => self::trabajadoresDeclaraciones($search))
                        ->getOptionLabelUsing(fn ($value): ?string => ($trabajador = Trabajador::query()->find($value))
                            ? self::etiquetaTrabajador($trabajador)
                            : null)
                        ->searchable()->preload()->live()
                        ->visible(fn (Get $get): bool => $get('TipoPersonaOrigen') === RosCaso::PERSONA_TRABAJADOR)
                        ->required(fn (Get $get): bool => $get('TipoPersonaOrigen') === RosCaso::PERSONA_TRABAJADOR)
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if ($state) {
                                $set('personas', app(RosPrefillService::class)->personasParaTrabajador((int) $state));
                            }
                        }),
                ])->columns(3),
            Forms\Components\Section::make('SECCIÓN I · Información del reporte')->schema([
                Forms\Components\TextInput::make('NumeroReporte')->label('1. Número'),
                Forms\Components\DatePicker::make('FechaReporte')->label('2. Fecha')->native(false),
                Forms\Components\Select::make('ClaseReporte')
                    ->label('3. Clase')
                    ->options(['INICIAL' => 'a. Reporte Inicial', 'CORRECCION' => 'b. Corrección del anterior', 'AMPLIACION' => 'c. Ampliación del anterior'])
                    ->required()->default('INICIAL')->live(),
                Forms\Components\TextInput::make('NumeroReporteAnterior')
                    ->label('4. Referencia del reporte anterior')
                    ->visible(fn (Get $get): bool => in_array($get('ClaseReporte'), ['CORRECCION', 'AMPLIACION'], true))
                    ->required(fn (Get $get): bool => in_array($get('ClaseReporte'), ['CORRECCION', 'AMPLIACION'], true)),
                Forms\Components\DatePicker::make('FechaReporteAnterior')
                    ->label('5. Fecha del reporte que se corrige o amplía')->native(false)
                    ->visible(fn (Get $get): bool => in_array($get('ClaseReporte'), ['CORRECCION', 'AMPLIACION'], true))
                    ->required(fn (Get $get): bool => in_array($get('ClaseReporte'), ['CORRECCION', 'AMPLIACION'], true)),
                Forms\Components\Select::make('DelitoPrecedente')
                    ->label('6. Presunto delito precedente')->options(fn (): array => self::catalogo('DELITO_PRECEDENTE'))->searchable()->preload(),
            ])->columns(3),
        ];
    }

    private static function pasoEmpresa(): array
    {
        return [
            Forms\Components\Section::make('SECCIÓN II · Información de la empresa que elabora el reporte')->schema([
                Forms\Components\Hidden::make('EnteSupervisor')->default('SBS/UIF-PERU'),
                Forms\Components\Placeholder::make('ente_supervisor_ros')->label('7. Ente Supervisor')->content('SBS/UIF-Perú · El formato indica no llenar este numeral.'),
                Forms\Components\Select::make('TipoSujetoObligado')->label('8. Tipo de Sujeto Obligado')->options(fn (): array => self::catalogo('TIPO_SUJETO'))->searchable()->required(),
                Forms\Components\TextInput::make('CodigoEmpresa')->label('9. Código de la empresa SBS'),
                Forms\Components\TextInput::make('CodigoOficialCumplimiento')->label('10. Código Oficial de Cumplimiento'),
                Forms\Components\TextInput::make('OficinaInformante')->label('11. Sucursal, agencia u oficina que informó'),
                Forms\Components\TextInput::make('OficinaOperacion')->label('12. Sucursal, agencia u oficina donde se realizó la operación'),
                Forms\Components\TextInput::make('DepartamentoOperacion')->label('13. Departamento'),
                Forms\Components\TextInput::make('ProvinciaOperacion')->label('13. Provincia'),
                Forms\Components\TextInput::make('DistritoOperacion')->label('13. Distrito'),
            ])->columns(3),
        ];
    }

    private static function pasoPersonas(): array
    {
        return [
            Forms\Components\Section::make('14. Personas que intervienen en la operación')
                ->description('Registre cada persona natural o jurídica. Los reportes anteriores quedan asociados a la persona correspondiente.')
                ->schema([
                    Forms\Components\Repeater::make('personas')
                        ->relationship()
                        ->label('Personas relacionadas')
                        ->schema([
                            Forms\Components\Select::make('TipoPersona')
                                ->label('Tipo de persona')
                                ->options(['NATURAL' => 'Persona natural', 'JURIDICA' => 'Persona jurídica'])
                                ->required()->default('NATURAL')->live()
                                ->afterStateUpdated(function ($state, Set $set): void {
                                    if ($state === 'JURIDICA') {
                                        $set('TipoDocumento', 'RUC');
                                        $set('EsPep', false);
                                    }
                                }),
                            Forms\Components\Select::make('ClienteID')->label('Cliente existente para precarga')->options(fn () => Cliente::query()->limit(200)->pluck('NombresApellidos', 'ClienteID'))->searchable(),

                            Forms\Components\TextInput::make('ApellidoPaterno')->label('15. Apellido Paterno')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\TextInput::make('ApellidoMaterno')->label('16. Apellido Materno')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\TextInput::make('Nombres')->label('17. Nombres')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\DatePicker::make('FechaNacimiento')->label('18. Fecha de nacimiento')->native(false)->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\TextInput::make('Nacionalidad')->label('19. Nacionalidad')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\TextInput::make('ProfesionOcupacion')->label('20. Profesión u ocupación')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\Toggle::make('EsPep')
                                ->label('20.a. ¿El reportado es un PEP?')
                                ->default(false)
                                ->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\Select::make('TipoDocumento')->label('21. Tipo de Documento de Identidad')->options(fn (): array => self::catalogo('TIPO_DOCUMENTO'))->searchable()->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL')->dehydrated(),
                            Forms\Components\TextInput::make('NumeroDocumento')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '47. Número de R.U.C.' : '22. N° de documento'),
                            Forms\Components\TextInput::make('PaisEmisionDocumento')->label('23. País de emisión del documento')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),

                            Forms\Components\TextInput::make('RazonSocial')->label('46. Denominación o razón social')->visible(fn (Get $get): bool => $get('TipoPersona') === 'JURIDICA'),
                            Forms\Components\Textarea::make('ObjetoSocial')->label('48. Objeto social')->visible(fn (Get $get): bool => $get('TipoPersona') === 'JURIDICA')->columnSpanFull(),

                            Forms\Components\TextInput::make('PaisDomicilio')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '51. País' : '24. País'),
                            Forms\Components\TextInput::make('DepartamentoDomicilio')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '52. Departamento' : '25. Departamento'),
                            Forms\Components\TextInput::make('ProvinciaDomicilio')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '53. Provincia' : '26. Provincia'),
                            Forms\Components\TextInput::make('DistritoDomicilio')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '54. Distrito' : '27. Distrito'),
                            Forms\Components\TextInput::make('UrbanizacionSector')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '55. Urbanización / sector / AA.HH.' : '28. Urbanización / sector / AA.HH.'),
                            Forms\Components\Select::make('TipoVia')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '56. Vía' : '29. Vía')->options(fn (): array => self::catalogo('VIA'))->searchable(),
                            Forms\Components\TextInput::make('NombreVia')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '57. Nombre de la vía' : '30. Nombre de la vía'),
                            Forms\Components\TextInput::make('NumeroDomicilio')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '58. Número / Mz. / Lt.' : '31. Número / Mz. / Lt.'),
                            Forms\Components\TextInput::make('Telefono')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '59. Teléfono 1' : '32. Teléfono 1'),
                            Forms\Components\TextInput::make('Telefono2')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '59. Teléfono 2' : '32. Teléfono 2'),
                            Forms\Components\TextInput::make('Telefono3')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '59. Teléfono 3' : '32. Teléfono 3'),

                            Forms\Components\TextInput::make('Correo')->label('33. Dirección de correo electrónico')->email()->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\Select::make('EstadoCivil')->label('34. Estado Civil')->options(fn (): array => self::catalogo('ESTADO_CIVIL'))->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL')->live(),
                            Forms\Components\TextInput::make('ConyugeApellidoPaterno')->label('35. Apellido Paterno del cónyuge/conviviente')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL' && in_array($get('EstadoCivil'), ['CASADO', 'CONCUBINO'], true)),
                            Forms\Components\TextInput::make('ConyugeApellidoMaterno')->label('36. Apellido Materno del cónyuge/conviviente')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL' && in_array($get('EstadoCivil'), ['CASADO', 'CONCUBINO'], true)),
                            Forms\Components\TextInput::make('ConyugeNombres')->label('37. Nombres del cónyuge/conviviente')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL' && in_array($get('EstadoCivil'), ['CASADO', 'CONCUBINO'], true)),
                            Forms\Components\CheckboxList::make('CondicionActividad')
                                ->label('38. Condición de su actividad económica')
                                ->options(['INDEPENDIENTE' => 'Independiente', 'DEPENDIENTE' => 'Dependiente', 'NO_APLICA' => 'N/A'])
                                ->columns(3)
                                ->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL')
                                ->afterStateHydrated(function (Forms\Components\CheckboxList $component, $state): void {
                                    $component->state(is_array($state) ? $state : array_values(array_filter(explode(',', (string) $state))));
                                })
                                ->dehydrateStateUsing(fn ($state): string => implode(',', is_array($state) ? $state : [])),
                            Forms\Components\TextInput::make('Empleador')->label('39. Nombre del empleador')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\Select::make('MonedaIngreso')->label('40. Moneda del ingreso mensual')->options(fn (): array => self::catalogo('MONEDA'))->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\TextInput::make('IngresoMensual')->numeric()->label('40. Ingreso promedio aproximado mensual')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),
                            Forms\Components\TextInput::make('TelefonoLaboral')->label('41. Teléfono de trabajo')->visible(fn (Get $get): bool => $get('TipoPersona') === 'NATURAL'),

                            Forms\Components\Select::make('CondicionParticipacion')
                                ->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '61. Condición en la que interviene' : '42. Condición en la que interviene')
                                ->options(fn (): array => self::catalogo('CONDICION_PARTICIPACION'))->searchable(),
                            Forms\Components\TextInput::make('PartidaElectronica')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '49. Partida Electrónica SUNARP' : '43. Partida Electrónica SUNARP'),
                            Forms\Components\TextInput::make('SedeRegistral')->label(fn (Get $get): string => $get('TipoPersona') === 'JURIDICA' ? '50. Sede Registral SUNARP' : '44. Sede Registral SUNARP'),
                            Forms\Components\Select::make('MonedaIngresoAnual')->label('60. Moneda del ingreso anual')->options(fn (): array => self::catalogo('MONEDA'))->visible(fn (Get $get): bool => $get('TipoPersona') === 'JURIDICA'),
                            Forms\Components\TextInput::make('IngresoAnual')->numeric()->label('60. Ingresos declarados en la última DJ anual')->visible(fn (Get $get): bool => $get('TipoPersona') === 'JURIDICA'),

                            Forms\Components\Repeater::make('reportesPrevios')
                                ->relationship()
                                ->label(fn (Get $get): string => $get('../TipoPersona') === 'JURIDICA' ? '62. Reportes anteriores relacionados' : '45. Reportes anteriores relacionados')
                                ->schema([
                                    Forms\Components\TextInput::make('NumeroReporte')->label('a. Número de reporte')->required(),
                                    Forms\Components\DatePicker::make('FechaReporte')->label('b. Fecha del reporte')->native(false),
                                    Forms\Components\Select::make('EnviadoA')->label('c. Enviado a')->options(['SI' => 'Sí', 'NO' => 'No']),
                                    Forms\Components\Textarea::make('InformacionRelevante')->label('d. Información relevante')->columnSpanFull(),
                                ])->columns(3)->defaultItems(0)->collapsible()->columnSpanFull(),

                            Forms\Components\Section::make('63–85. Información del representante legal')
                                ->visible(fn (Get $get): bool => $get('TipoPersona') === 'JURIDICA')
                                ->schema([
                                    Forms\Components\TextInput::make('RepApellidoPaterno')->label('63. Apellido Paterno'),
                                    Forms\Components\TextInput::make('RepApellidoMaterno')->label('64. Apellido Materno'),
                                    Forms\Components\TextInput::make('RepNombres')->label('65. Nombres'),
                                    Forms\Components\DatePicker::make('RepFechaNacimiento')->label('66. Fecha de nacimiento')->native(false),
                                    Forms\Components\TextInput::make('RepNacionalidad')->label('67. Nacionalidad'),
                                    Forms\Components\TextInput::make('RepProfesionOcupacion')->label('68. Profesión u ocupación'),
                                    Forms\Components\Select::make('RepTipoDocumento')->label('69. Tipo de Documento de Identidad')->options(fn (): array => self::catalogo('TIPO_DOCUMENTO'))->searchable(),
                                    Forms\Components\TextInput::make('RepNumeroDocumento')->label('70. N° de documento'),
                                    Forms\Components\TextInput::make('RepPaisEmisionDocumento')->label('71. País de emisión del documento'),
                                    Forms\Components\TextInput::make('RepPaisDomicilio')->label('72. País'),
                                    Forms\Components\TextInput::make('RepDepartamentoDomicilio')->label('73. Departamento'),
                                    Forms\Components\TextInput::make('RepProvinciaDomicilio')->label('74. Provincia'),
                                    Forms\Components\TextInput::make('RepDistritoDomicilio')->label('75. Distrito'),
                                    Forms\Components\TextInput::make('RepUrbanizacionSector')->label('76. Urbanización / sector / AA.HH.'),
                                    Forms\Components\Select::make('RepTipoVia')->label('77. Vía')->options(fn (): array => self::catalogo('VIA'))->searchable(),
                                    Forms\Components\TextInput::make('RepNombreVia')->label('78. Nombre de la vía'),
                                    Forms\Components\TextInput::make('RepNumeroDomicilio')->label('79. Número / Mz. / Lt.'),
                                    Forms\Components\TextInput::make('RepTelefono')->label('80. Teléfono 1'),
                                    Forms\Components\TextInput::make('RepTelefono2')->label('80. Teléfono 2'),
                                    Forms\Components\TextInput::make('RepTelefono3')->label('80. Teléfono 3'),
                                    Forms\Components\TextInput::make('RepCorreo')->label('81. Dirección de correo electrónico')->email(),
                                    Forms\Components\Select::make('RepEstadoCivil')->label('82. Estado Civil')->options(fn (): array => self::catalogo('ESTADO_CIVIL'))->live(),
                                    Forms\Components\TextInput::make('RepConyugeApellidoPaterno')->label('83. Apellido Paterno del cónyuge/conviviente')->visible(fn (Get $get): bool => in_array($get('RepEstadoCivil'), ['CASADO', 'CONCUBINO'], true)),
                                    Forms\Components\TextInput::make('RepConyugeApellidoMaterno')->label('84. Apellido Materno del cónyuge/conviviente')->visible(fn (Get $get): bool => in_array($get('RepEstadoCivil'), ['CASADO', 'CONCUBINO'], true)),
                                    Forms\Components\TextInput::make('RepConyugeNombres')->label('85. Nombres del cónyuge/conviviente')->visible(fn (Get $get): bool => in_array($get('RepEstadoCivil'), ['CASADO', 'CONCUBINO'], true)),
                                ])->columns(3)->columnSpanFull(),

                            Forms\Components\Select::make('RelacionEmpresa')
                                ->label('86. Relación con la empresa que comunica la operación')
                                ->options([
                                    'CLIENTE' => 'a. Cliente',
                                    'DIRECTOR' => 'b. Director',
                                    'EMPLEADO' => 'c. Empleado',
                                    'ACCIONISTA' => 'd. Accionista',
                                    'INVERSIONISTA' => 'e. Inversionista',
                                    'GERENTE' => 'Gerente',
                                    'OTRO' => 'f. Otro',
                                ])->live(),
                            Forms\Components\TextInput::make('RelacionEmpresaOtro')->label('86.f. Si es otro, indicar cuál')->visible(fn (Get $get): bool => $get('RelacionEmpresa') === 'OTRO'),
                            Forms\Components\Select::make('VinculadoActualmente')
                                ->label('87. ¿Está vinculada aún a la empresa?')
                                ->options(['1' => 'a. Sí', '0' => 'b. No'])
                                ->placeholder('Seleccione')->live(),
                            Forms\Components\Select::make('MotivoDesvinculacion')
                                ->label('88. Si no lo está, ¿por qué?')
                                ->options([
                                    'RETIRO_VOLUNTARIO' => 'a. Retiro voluntario',
                                    'DECISION_EMPRESA' => 'b. Decisión de la Institución',
                                    'SUSPENSION' => 'c. Suspensión',
                                ])->visible(fn (Get $get): bool => in_array($get('VinculadoActualmente'), [false, 0, '0'], true)),
                            Forms\Components\DatePicker::make('FechaDesvinculacion')->label('88. Fecha de desvinculación')->native(false)->visible(fn (Get $get): bool => in_array($get('VinculadoActualmente'), [false, 0, '0'], true)),
                        ])
                        ->columns(3)->defaultItems(0)->addActionLabel('Agregar persona relacionada')
                        ->itemLabel(fn (array $state): ?string => $state['RazonSocial'] ?? (trim(($state['Nombres'] ?? '').' '.($state['ApellidoPaterno'] ?? '')) ?: null))
                        ->cloneable()->collapsible(),
                ]),
        ];
    }

    private static function pasoOperaciones(): array
    {
        return [
            Forms\Components\Section::make('SECCIÓN IV · Información de la Operación Sospechosa')->schema([
                Forms\Components\Select::make('Moneda')->label('89. Moneda')->options(fn (): array => self::catalogo('MONEDA'))->default('PEN')->required(),
                Forms\Components\TextInput::make('MontoTotal')->label('89. Monto total involucrado')->numeric(),
                Forms\Components\DatePicker::make('FechaOperacionDesde')->label('90. Desde')->native(false),
                Forms\Components\DatePicker::make('FechaOperacionHasta')->label('90. Hasta')->native(false)->afterOrEqual('FechaOperacionDesde'),
                Forms\Components\Select::make('Alcance')->label('91. Alcance')->options(['NACIONAL' => 'Nacional', 'INTERNACIONAL' => 'Internacional'])->default('NACIONAL')->required()->live(),
                Forms\Components\TextInput::make('PaisesRelacionados')->label('91. País(es)')->visible(fn (Get $get): bool => $get('Alcance') === 'INTERNACIONAL')->columnSpanFull(),
            ])->columns(3),
            Forms\Components\Repeater::make('operaciones')
                ->relationship()->label('92–93. Productos o servicios utilizados y detalle')
                ->addActionLabel('Agregar operación')
                ->schema([
                    Forms\Components\Hidden::make('ClienteID'),
                    Forms\Components\Hidden::make('CreditoID'),
                    Forms\Components\Hidden::make('PagoID'),
                    Forms\Components\Select::make('CodigoProducto')
                        ->label('92. Producto o servicio')->options(fn (): array => self::catalogo('PRODUCTO_SERVICIO'))->searchable()->required()->live()
                        ->afterStateUpdated(fn ($state, Set $set) => $set('ProductoServicio', RosCatalogoItem::query()->where('Tipo', 'PRODUCTO_SERVICIO')->where('Codigo', $state)->value('Nombre'))),
                    Forms\Components\TextInput::make('Detalle')
                        ->label('92. Si es otro, indicar el producto o servicio utilizado')
                        ->visible(fn (Get $get): bool => $get('CodigoProducto') === 'ff')
                        ->required(fn (Get $get): bool => $get('CodigoProducto') === 'ff')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('ProductoServicio')
                        ->label('93. Producto o servicio')
                        ->required(),
                    Forms\Components\TextInput::make('NumeroOperacion')->label('93. N° o Código'),
                    Forms\Components\Select::make('Moneda')->label('93. Moneda')->options(fn (): array => self::catalogo('MONEDA'))->default('PEN'),
                    Forms\Components\TextInput::make('Monto')->label('93. Monto')->numeric(),
                    Forms\Components\DatePicker::make('FechaOperacion')->label('93. Fecha')->native(false),
                ])->columns(3)->orderColumn('Orden')->addActionLabel('Agregar producto / servicio')->collapsible(),
            Forms\Components\Textarea::make('DocumentacionComplementaria')
                ->label('94. Información o documentación complementaria que se remite como sustento')
                ->rows(5)->columnSpanFull(),
            Forms\Components\Repeater::make('adjuntos')
                ->relationship()->label('Sustentos adjuntos de uso interno')
                ->helperText('Los archivos se conservan como evidencia interna y no se agregan como campos al formato ROS.')
                ->schema([
                    Forms\Components\FileUpload::make('RutaArchivo')
                        ->label('Archivo')->disk('local')->directory('cumplimiento-sbs/evidencias')
                        ->storeFileNamesIn('NombreOriginal')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(20480)->required(),
                    Forms\Components\TextInput::make('Descripcion')->label('Descripción interna')->columnSpanFull(),
                ])->columns(2)->defaultItems(0)->collapsible(),
        ];
    }

    private static function pasoAnalisis(): array
    {
        return [
            Forms\Components\Section::make('SECCIÓN V · 95. Descripción de la operación sospechosa')->schema([
                Forms\Components\Textarea::make('DescripcionHechos')->label('95.a. Descripción cronológica de los hechos')->rows(5)->required()->columnSpanFull(),
                Forms\Components\Textarea::make('RelacionPersonas')->label('95.b. Forma en que se relacionan las personas')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('RelacionProductosServicios')->label('95.c. Cómo se relacionan los productos o servicios utilizados')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('CaracteristicasSospechosas')->label('95.d. Características o consideraciones por las que se calificó como sospechosa')->rows(4)->required()->columnSpanFull(),
                Forms\Components\Textarea::make('ExplicacionesJustificaciones')->label('95.e. Explicaciones o justificaciones recibidas')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('RelacionOtrasOperaciones')->label('95.f. Relación con otras operaciones, reportes o clientes')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('InformacionExcluida')->label('95.g. Información excluida y motivo')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('CambiosReporteAnterior')->label('95.h. Cambios o adiciones al reporte anterior')->rows(4)->visible(fn (Get $get): bool => in_array($get('ClaseReporte'), ['CORRECCION', 'AMPLIACION'], true))->columnSpanFull(),
                Forms\Components\Textarea::make('FuentesConsultadas')->label('95.i. Fuentes y/o bases de datos consultadas')->rows(4)->required()->columnSpanFull(),
                Forms\Components\Select::make('SectorEconomico')->label('96. Sector económico')->options(fn (): array => self::catalogo('SECTOR'))->searchable()->live(),
                Forms\Components\Select::make('ActividadEconomica')
                    ->label('96. Actividad económica')
                    ->options(function (Get $get): array {
                        $sectorCodigo = $get('SectorEconomico');
                        if (! $sectorCodigo) {
                            return self::catalogo('ACTIVIDAD');
                        }
                        $sector = RosCatalogoItem::query()->where('Tipo', 'SECTOR')->where('Codigo', $sectorCodigo)->value('Nombre');

                        return RosCatalogoItem::query()->where('Tipo', 'ACTIVIDAD')->where('Grupo', $sector)->where('Activo', true)->orderBy('Orden')->pluck('Nombre', 'Codigo')->all();
                    })->searchable(),
            ])->columns(2),
            Forms\Components\Repeater::make('senalesAlerta')
                ->relationship()->label('97. Señales de Alerta')
                ->schema([
                    Forms\Components\Select::make('Tipo')->label('Tipo')->options(['REPORTADO' => 'a. Del reportado', 'OPERACION' => 'b. De la operación'])->required()->live(),
                    Forms\Components\Select::make('Codigo')
                        ->label('Señal de alerta')
                        ->options(fn (Get $get): array => self::catalogo($get('Tipo') === 'OPERACION' ? 'SENAL_OPERACION' : 'SENAL_REPORTADO'))
                        ->searchable()->live()->required()
                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                            $tipo = $get('Tipo') === 'OPERACION' ? 'SENAL_OPERACION' : 'SENAL_REPORTADO';
                            $set('Descripcion', RosCatalogoItem::query()->where('Tipo', $tipo)->where('Codigo', $state)->value('Nombre'));
                        }),
                    Forms\Components\Hidden::make('Descripcion')->dehydrated(),
                ])->columns(2)->addActionLabel('Agregar señal de alerta')->collapsible(),
            Forms\Components\Repeater::make('tipologias')
                ->relationship()->label('98. Posibles Tipologías relacionadas')
                ->schema([
                    Forms\Components\Select::make('Codigo')->label('Tipología')->options(fn (): array => self::catalogo('TIPOLOGIA'))->searchable()->live()->required()
                        ->afterStateUpdated(fn ($state, Set $set) => $set('Descripcion', RosCatalogoItem::query()->where('Tipo', 'TIPOLOGIA')->where('Codigo', $state)->value('Nombre'))),
                    Forms\Components\Hidden::make('Descripcion')->dehydrated(),
                ])->columns(1)->defaultItems(0)->collapsible(),
        ];
    }

    private static function pasoCierre(): array
    {
        return [
            Forms\Components\Section::make('Calificación y envío')
                ->description('Información interna del flujo. No forma parte de los numerales 1–98 del formato ROS.')
                ->schema([
                    Forms\Components\Placeholder::make('calificado_en')->label('Calificado como ROS')->content(fn (?RosCaso $record): string => $record?->FechaCalificadoRos?->format('d/m/Y H:i') ?? 'Pendiente'),
                    Forms\Components\TextInput::make('NumeroEnvioRosel')->label('Número o constancia ROSEL')->disabled(),
                    Forms\Components\DatePicker::make('FechaEnvioRosel')->label('Fecha de envío ROSEL')->disabled(),
                    Forms\Components\FileUpload::make('ConstanciaRoselRuta')->label('Constancia de envío')->disk('local')->directory('cumplimiento-sbs/constancias-rosel')->disabled(),
                    Forms\Components\Placeholder::make('bloqueo')->label('Edición')->content(fn (?RosCaso $record): string => $record?->Bloqueado ? 'Bloqueado por envío reportado' : 'Editable según el flujo'),
                ])->columns(2),
        ];
    }

    private static function catalogo(string $tipo, array $respaldo = []): array
    {
        try {
            return RosCatalogoItem::opciones($tipo) ?: $respaldo;
        } catch (Throwable) {
            return $respaldo;
        }
    }

    private static function etiquetaClienteJalud(Cliente $cliente): string
    {
        return trim(($cliente->DNI ? "{$cliente->DNI} · " : '').($cliente->NombresApellidos ?: trim("{$cliente->ApellidoPaterno} {$cliente->ApellidoMaterno} {$cliente->Nombres}")));
    }

    private static function trabajadoresDeclaraciones(?string $search = null): array
    {
        return Trabajador::query()
            ->where('Activo', true)
            ->when($search, fn (Builder $query, string $term) => $query->where(fn (Builder $filtro) => $filtro
                ->where('NumeroDocumento', 'like', "%{$term}%")
                ->orWhere('Nombres', 'like', "%{$term}%")
                ->orWhere('ApellidoPaterno', 'like', "%{$term}%")
                ->orWhere('ApellidoMaterno', 'like', "%{$term}%")))
            ->orderBy('ApellidoPaterno')
            ->orderBy('Nombres')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Trabajador $trabajador): array => [$trabajador->TrabajadorID => self::etiquetaTrabajador($trabajador)])
            ->all();
    }

    private static function etiquetaTrabajador(Trabajador $trabajador): string
    {
        return trim(($trabajador->NumeroDocumento ? "{$trabajador->NumeroDocumento} · " : '').$trabajador->NombreCompleto);
    }

    private static function creditosJalud(?int $clienteId = null, ?string $search = null): array
    {
        return Credito::query()
            ->when($clienteId, fn (Builder $query, int $id) => $query->whereHas(
                'proposicion',
                fn (Builder $proposicion) => $proposicion->where('ClienteID', $id),
            ))
            ->when($search, fn (Builder $query, string $term) => $query->whereHas(
                'proposicion',
                fn (Builder $proposicion) => $proposicion
                    ->where('CodigoCredito', 'like', "%{$term}%")
                    ->orWhereHas('cliente', fn (Builder $cliente) => $cliente
                        ->where('DNI', 'like', "%{$term}%")
                        ->orWhere('NombresApellidos', 'like', "%{$term}%")),
            ))
            ->with(['proposicion.cliente'])
            ->latest('CreditoID')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Credito $credito): array => [$credito->CreditoID => self::etiquetaCreditoJalud($credito)])
            ->all();
    }

    private static function etiquetaCreditoJalud(Credito $credito): string
    {
        $codigo = $credito->proposicion?->CodigoCredito ?? "Crédito #{$credito->CreditoID}";
        $cliente = $credito->proposicion?->cliente?->NombresApellidos;

        return $cliente ? "{$codigo} · {$cliente}" : $codigo;
    }

    private static function pagosJalud(int $creditoId, ?string $search = null): array
    {
        return Pago::query()
            ->where('CreditoID', $creditoId)
            ->where('Activo', true)
            ->when($search, fn (Builder $query, string $term) => $query->where(fn (Builder $filtro) => $filtro
                ->where('PagoID', 'like', "%{$term}%")
                ->orWhere('Comentario', 'like', "%{$term}%")))
            ->latest('PagoID')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Pago $pago): array => [$pago->PagoID => self::etiquetaPagoJalud($pago)])
            ->all();
    }

    private static function etiquetaPagoJalud(Pago $pago): string
    {
        return "Pago #{$pago->PagoID} · {$pago->FechaPago?->format('d/m/Y')} · S/ {$pago->MontoPagado}";
    }

    private static function precargarConfiguracion($sedeId, Set $set): void
    {
        if (! $sedeId) {
            return;
        }

        $configuracion = app(RosPrefillService::class)->configuracion((int) $sedeId);
        $mapeo = ['Departamento' => 'DepartamentoOperacion', 'Provincia' => 'ProvinciaOperacion', 'Distrito' => 'DistritoOperacion'];

        foreach ($configuracion as $campo => $valor) {
            $set($mapeo[$campo] ?? $campo, $valor);
        }
    }

    private static function aplicarPrecargaOperacion(int $creditoId, ?int $pagoId, Set $set): void
    {
        $precarga = app(RosPrefillService::class)->paraOperacion($creditoId, $pagoId);

        foreach ($precarga['caso'] as $campo => $valor) {
            $set($campo, $valor);
        }

        $set('personas', $precarga['personas']);
        $set('operaciones', $precarga['operaciones']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['sede', 'zona', 'cliente', 'trabajador', 'credito.proposicion']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('CodigoInterno')->label('Caso')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('NumeroReporte')->label('Reporte')->searchable()->placeholder('Pendiente'),
                Tables\Columns\BadgeColumn::make('OrigenRos')->label('Origen')
                    ->formatStateUsing(fn (string $state): string => $state === RosCaso::ORIGEN_ROP ? 'ROP' : 'Directo'),
                Tables\Columns\BadgeColumn::make('Estado')->formatStateUsing(fn (string $state): string => RosCaso::estados()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        RosCaso::ESTADO_REPORTADO => 'success', RosCaso::ESTADO_DESCARTADO => 'gray', RosCaso::ESTADO_APROBADO => 'warning', RosCaso::ESTADO_EVALUACION => 'info', default => 'primary'
                    }),
                Tables\Columns\TextColumn::make('persona_origen')->label('Persona')
                    ->state(fn (RosCaso $record): ?string => $record->TipoPersonaOrigen === RosCaso::PERSONA_TRABAJADOR
                        ? $record->trabajador?->NombreCompleto
                        : $record->cliente?->NombresApellidos)
                    ->placeholder('Sin persona')->wrap(),
                Tables\Columns\TextColumn::make('credito.proposicion.CodigoCredito')->label('Crédito')->placeholder('Sin crédito'),
                Tables\Columns\TextColumn::make('zona.Nombre')->label('Zona')->placeholder('Sin zona'),
                Tables\Columns\TextColumn::make('sede.Nombre')->label('Sede')->visible(fn (): bool => auth()->user()?->can('ver_todos_los_casos_sbs') ?? false),
                Tables\Columns\TextColumn::make('MontoTotal')->label('Monto')->formatStateUsing(fn ($state, RosCaso $record): string => $state === null ? '—' : "{$record->Moneda} ".number_format((float) $state, 2))->sortable(),
                Tables\Columns\TextColumn::make('FechaReporte')->label('Fecha del reporte')->date('d/m/Y')->sortable()->placeholder('Pendiente'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('Estado')->options(RosCaso::estados()),
                Tables\Filters\SelectFilter::make('ZonaID')->label('Zona')->options(Zona::query()->where('Activo', true)->orderBy('Nombre')->pluck('Nombre', 'ZonaID')),
                Tables\Filters\SelectFilter::make('SedeID')->label('Sede')->options(Sede::query()->where('Activo', true)->orderBy('Nombre')->pluck('Nombre', 'SedeID'))->visible(fn (): bool => auth()->user()?->can('ver_todos_los_casos_sbs') ?? false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn (RosCaso $record): bool => static::canEdit($record)),
            ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRosCasos::route('/'),
            'create' => Pages\CreateRosCaso::route('/create'),
            'view' => Pages\ViewRosCaso::route('/{record}'),
            'edit' => Pages\EditRosCaso::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [RelationManagers\AuditoriasRelationManager::class];
    }
}
