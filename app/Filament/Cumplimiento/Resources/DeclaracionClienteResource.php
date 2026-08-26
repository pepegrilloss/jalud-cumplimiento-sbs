<?php

namespace App\Filament\Cumplimiento\Resources;

use App\Enums\Declaraciones\EstadoCivil;
use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Enums\Declaraciones\TipoDocumentoIdentidad;
use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\Pages;
use App\Filament\Cumplimiento\Resources\DeclaracionClienteResource\RelationManagers;
use App\Filament\Forms\CamposGeograficos;
use App\Models\Cliente;
use App\Models\DeclaracionCliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class DeclaracionClienteResource extends Resource
{
    protected static ?string $model = DeclaracionCliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Declaraciones juradas';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'declaración de cliente';

    protected static ?string $pluralModelLabel = 'Declaraciones de clientes';

    protected static ?string $recordTitleAttribute = 'NombreCompleto';

    protected static ?string $slug = 'declaraciones-clientes';

    protected static ?int $navigationSort = 20;

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
        return $form->schema([Forms\Components\Wizard::make([
            Forms\Components\Wizard\Step::make('Datos generales')->icon('heroicon-o-user')->schema(self::generales()),
            Forms\Components\Wizard\Step::make('PEP')->icon('heroicon-o-shield-exclamation')->schema(self::pep()),
            Forms\Components\Wizard\Step::make('Beneficiario')->icon('heroicon-o-users')->schema(self::beneficiario()),
            Forms\Components\Wizard\Step::make('Revisión')->icon('heroicon-o-clipboard-document-check')->schema([
                Forms\Components\Section::make('Identificación y contacto')->schema([
                    Forms\Components\Placeholder::make('rev_identidad')->label('Cliente')->content(fn (Forms\Get $get) => trim("{$get('Nombres')} {$get('Apellidos')}")),
                    Forms\Components\Placeholder::make('rev_documento')->label('Documento')->content(fn (Forms\Get $get) => trim("{$get('TipoDocumento')} {$get('NumeroDocumento')}")),
                    Forms\Components\Placeholder::make('rev_contacto')->label('Contacto')->content(fn (Forms\Get $get) => trim("{$get('Celular')} {$get('Correo')}")),
                ])->columns(3),
                Forms\Components\Section::make('PEP y beneficiario')->schema([
                    Forms\Components\Placeholder::make('rev_pep')->label('Condición PEP')->content(fn (Forms\Get $get) => \App\Support\Declaraciones\PresentacionDeclaracion::etiqueta($get('EstadoPep'))),
                    Forms\Components\Placeholder::make('rev_pariente')->label('Relación PEP')->content(fn (Forms\Get $get) => $get('EsParientePep') ? 'Sí · '.count($get('pepRelacionados') ?? []).' relacionado(s)' : 'No'),
                    Forms\Components\Placeholder::make('rev_beneficiario')->label('Beneficiario')->content(fn (Forms\Get $get) => \App\Support\Declaraciones\PresentacionDeclaracion::etiqueta($get('TipoBeneficiario'))),
                    Forms\Components\Placeholder::make('rev_origen')->label('Origen de fondos')->content(fn (Forms\Get $get) => (string) $get('OrigenFondos')),
                ])->columns(2),
                Forms\Components\TextInput::make('NumeroCuentaFinanciera')->label('N.º de cuenta financiera usada para procesar la transacción'),
                Forms\Components\DatePicker::make('FechaDeclaracion')->label('Fecha de declaración')->required()->default(now()),
            ])->columns(2),
            Forms\Components\Wizard\Step::make('Documento')->icon('heroicon-o-document-arrow-down')->schema([
                Forms\Components\Placeholder::make('documento_info')->hiddenLabel()->content('Al finalizar se habilitará la generación de la Declaración Jurada de conocimiento del cliente.'),
            ]),
        ])->columnSpanFull()
            ->submitAction(in_array($form->getOperation(), ['create', 'edit'], true) ? self::accionGuardarBorrador() : null)]);
    }

    private static function generales(): array
    {
        return [
            Forms\Components\Section::make('Cliente')->schema([
                Forms\Components\Select::make('ClienteID')->label('Cliente de Jalud')
                    ->options(fn (): array => self::clientesDisponiblesQuery()->orderBy('NombresApellidos')->limit(50)->get()->mapWithKeys(fn (Cliente $cliente) => [$cliente->getKey() => self::etiquetaCliente($cliente)])->all())
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => self::clientesDisponiblesQuery()
                        ->where(fn (Builder $query) => $query->where('DNI', 'like', "%{$search}%")->orWhere('NombresApellidos', 'like', "%{$search}%"))
                        ->orderBy('NombresApellidos')->limit(50)->get()
                        ->mapWithKeys(fn (Cliente $cliente) => [$cliente->getKey() => self::etiquetaCliente($cliente)])->all())
                    ->getOptionLabelUsing(fn ($value): ?string => ($cliente = self::clientesDisponiblesQuery()->find($value)) ? self::etiquetaCliente($cliente) : null)
                    ->helperText('Busque por DNI o nombres y apellidos en la base de datos de Jalud.')
                    ->required()->live()->disabledOn('edit')
                    ->afterStateUpdated(function ($state, Forms\Set $set): void {
                        $c = self::clientesDisponiblesQuery()->find($state);
                        if (! $c) {
                            return;
                        }$set('Nombres', $c->Nombres ?: $c->NombresApellidos);
                        $set('Apellidos', trim("{$c->ApellidoPaterno} {$c->ApellidoMaterno}"));
                        $set('NumeroDocumento', $c->DNI);
                        $set('DomicilioVia', $c->Domicilio);
                        $set('ConyugeConviviente', $c->ConyugeNombresApellidos);
                    }),
                Forms\Components\TextInput::make('Nombres')->required(), Forms\Components\TextInput::make('Apellidos')->required(),
                Forms\Components\Select::make('TipoDocumento')->label('Tipo de documento')->options(TipoDocumentoIdentidad::opciones())->default('DNI')->required()->live()->afterStateUpdated(fn ($state, Forms\Set $set) => $state !== 'OTRO' ? $set('TipoDocumentoOtro', null) : null),
                Forms\Components\TextInput::make('TipoDocumentoOtro')->label('Especifique tipo de documento')->required(fn (Forms\Get $get) => $get('TipoDocumento') === 'OTRO')->visible(fn (Forms\Get $get) => $get('TipoDocumento') === 'OTRO')->dehydratedWhenHidden(false),
                Forms\Components\TextInput::make('NumeroDocumento')->label('Número de documento')->required()->rules(fn (Forms\Get $get) => [new \App\Rules\DocumentoIdentidadValido($get('TipoDocumento'))]),
                CamposGeograficos::pais('Nacionalidad', 'País de nacionalidad'), Forms\Components\Select::make('EstadoCivil')->label('Estado civil')->options(collect(EstadoCivil::opciones())->except('CONVIVIENTE')->all())->required()->live()->afterStateUpdated(fn ($state, Forms\Set $set) => $state !== 'CASADO' ? $set('ConyugeConviviente', null) : null),
                Forms\Components\TextInput::make('ConyugeConviviente')->label('Cónyuge')->required(fn (Forms\Get $get) => $get('EstadoCivil') === 'CASADO')->visible(fn (Forms\Get $get) => $get('EstadoCivil') === 'CASADO')->dehydratedWhenHidden(false),
            ])->columns(3),
            Forms\Components\Section::make('Domicilio')->schema([
                Forms\Components\TextInput::make('DomicilioVia')->label('Jr. / Av. / Calle / Pasaje / Óvalo')->required()->columnSpan(2), Forms\Components\TextInput::make('DomicilioNumero')->label('N.º'),
                Forms\Components\TextInput::make('DomicilioInterior')->label('Dpto.-Int. N.º'), Forms\Components\TextInput::make('DomicilioZona')->label('Urb. / Complejo / Zona / Sector')->columnSpan(2),
                ...CamposGeograficos::domicilio('DomicilioDepartamento', 'DomicilioProvincia', 'DomicilioDistrito'),
            ])->columns(4),
            Forms\Components\Section::make('Contacto y relación')->schema([
                Forms\Components\TextInput::make('Ocupacion')->label('Ocupación')->required(), Forms\Components\TextInput::make('TelefonoFijo')->label('Teléfono fijo')->tel(),
                Forms\Components\TextInput::make('Celular')->tel(), Forms\Components\TextInput::make('Correo')->label('Correo electrónico')->email(),
                Forms\Components\Textarea::make('PropositoRelacion')->label('Propósito de la relación con el sujeto obligado')->required()->columnSpanFull(),
            ])->columns(4),
        ];
    }

    public static function clientesDisponiblesQuery(): Builder
    {
        $query = Cliente::withoutGlobalScope('sede')->where('Activo', true);

        return auth()->check() ? $query : $query->whereRaw('1 = 0');
    }

    private static function etiquetaCliente(Cliente $cliente): string
    {
        return trim(($cliente->DNI ? $cliente->DNI.' · ' : '').($cliente->NombresApellidos ?: trim("{$cliente->ApellidoPaterno} {$cliente->ApellidoMaterno} {$cliente->Nombres}")));
    }

    private static function pep(): array
    {
        $estados = ['SI_SOY' => 'Sí soy', 'SI_HE_SIDO' => 'Sí he sido', 'NO_SOY' => 'No soy', 'NO_HE_SIDO' => 'No he sido'];

        return [
            Forms\Components\Section::make('10.1. Persona Expuesta Políticamente')->schema([
                Forms\Components\Radio::make('EstadoPep')->label('¿Es o ha sido PEP?')->options($estados)->required()->live()->inline()->afterStateUpdated(function ($state, Forms\Set $set) {
                    if (! in_array($state, ['SI_SOY', 'SI_HE_SIDO'], true)) {
                        $set('PepCargo', null);
                        $set('PepInstitucion', null);
                        $set('pepFamiliares', []);
                    }
                }),
                Forms\Components\Radio::make('EstadoColaboradorAutoridad')->label('¿Ha sido colaborador directo de la máxima autoridad?')->options($estados)->required()->inline(),
                Forms\Components\TextInput::make('PepCargo')->label('Cargo')->required(fn (Forms\Get $get) => in_array($get('EstadoPep'), ['SI_SOY', 'SI_HE_SIDO'], true))->visible(fn (Forms\Get $get) => in_array($get('EstadoPep'), ['SI_SOY', 'SI_HE_SIDO'], true))->dehydratedWhenHidden(false),
                Forms\Components\TextInput::make('PepInstitucion')->label('Institución')->required(fn (Forms\Get $get) => in_array($get('EstadoPep'), ['SI_SOY', 'SI_HE_SIDO'], true))->visible(fn (Forms\Get $get) => in_array($get('EstadoPep'), ['SI_SOY', 'SI_HE_SIDO'], true))->dehydratedWhenHidden(false),
            ])->columns(2),
            Forms\Components\Repeater::make('pepFamiliares')->relationship()->label('10.2. Familiares de la persona PEP')->visible(fn (Forms\Get $get) => in_array($get('EstadoPep'), ['SI_SOY', 'SI_HE_SIDO'], true))->dehydratedWhenHidden(false)->schema([
                Forms\Components\TextInput::make('Nombres')->required(), Forms\Components\TextInput::make('Apellidos')->required(), Forms\Components\TextInput::make('Parentesco')->required(),
            ])->columns(3)->addActionLabel('Agregar familiar')->orderColumn('Orden')->defaultItems(0),
            Forms\Components\Section::make('10.3. Pariente de PEP')->schema([
                Forms\Components\Toggle::make('EsParientePep')->label('¿Es pariente de PEP?')->live()->afterStateUpdated(fn ($state, Forms\Set $set) => ! $state ? $set('pepRelacionados', []) : null),
                Forms\Components\Repeater::make('pepRelacionados')->relationship()->label('PEP relacionados')->visible(fn (Forms\Get $get) => (bool) $get('EsParientePep'))->schema([
                    Forms\Components\TextInput::make('Nombres')->required(), Forms\Components\TextInput::make('Apellidos')->required(), Forms\Components\TextInput::make('Parentesco')->required(),
                ])->dehydratedWhenHidden(false)->columns(3)->addActionLabel('Agregar PEP relacionado')->orderColumn('Orden')->defaultItems(0)->columnSpanFull(),
            ]),
        ];
    }

    private static function beneficiario(): array
    {
        return [Forms\Components\Section::make('Identidad del beneficiario de la operación')->schema([
            Forms\Components\Radio::make('TipoBeneficiario')->options(['YO_MISMO' => 'Yo mismo', 'TERCERO_NATURAL' => 'Tercero persona natural', 'PERSONA_JURIDICA' => 'Persona jurídica', 'ENTE_JURIDICO' => 'Ente jurídico'])->required()->live()->inline(),
            Forms\Components\Textarea::make('OrigenFondos')->label('Origen de los fondos/activos involucrados')->required()->columnSpanFull(),
            Forms\Components\Fieldset::make('Tercero persona natural')->visible(fn (Forms\Get $get) => $get('TipoBeneficiario') === 'TERCERO_NATURAL')->schema([
                Forms\Components\TextInput::make('TerceroNombresApellidos')->label('Nombres y apellidos')->required(),
                Forms\Components\Select::make('TerceroTipoDocumento')->label('Tipo de documento')->options(TipoDocumentoIdentidad::opciones())->required()->live()->afterStateUpdated(fn ($state, Forms\Set $set) => $state !== 'OTRO' ? $set('TerceroTipoDocumentoOtro', null) : null),
                Forms\Components\TextInput::make('TerceroTipoDocumentoOtro')->label('Especifique tipo de documento')->required(fn (Forms\Get $get) => $get('TerceroTipoDocumento') === 'OTRO')->visible(fn (Forms\Get $get) => $get('TerceroTipoDocumento') === 'OTRO')->dehydratedWhenHidden(false),
                Forms\Components\TextInput::make('TerceroNumeroDocumento')->label('Número')->required()->rules(fn (Forms\Get $get) => [new \App\Rules\DocumentoIdentidadValido($get('TerceroTipoDocumento'))]),
                Forms\Components\Radio::make('TerceroRepresentacion')->label('Representación')->options(['ESCRITURA_PUBLICA' => 'Poder por Escritura Pública', 'MANDATO' => 'Mandato'])->required()->inline(),
                Forms\Components\Radio::make('TerceroEstadoPep')->label('PEP')->options(['SI_ES' => 'Sí es', 'SI_HA_SIDO' => 'Sí ha sido', 'NO_ES' => 'No es', 'NO_HA_SIDO' => 'No ha sido'])->required()->live()->inline(),
                Forms\Components\TextInput::make('TerceroPepCargo')->label('Cargo')->visible(fn (Forms\Get $get) => in_array($get('TerceroEstadoPep'), ['SI_ES', 'SI_HA_SIDO'], true)),
                Forms\Components\TextInput::make('TerceroPepInstitucion')->label('Institución')->visible(fn (Forms\Get $get) => in_array($get('TerceroEstadoPep'), ['SI_ES', 'SI_HA_SIDO'], true)),
            ])->columns(2),
            Forms\Components\Fieldset::make('Persona jurídica / ente jurídico')->visible(fn (Forms\Get $get) => in_array($get('TipoBeneficiario'), ['PERSONA_JURIDICA', 'ENTE_JURIDICO'], true))->schema([
                Forms\Components\TextInput::make('EntidadRazonSocial')->label('Denominación o razón social')->required(), Forms\Components\TextInput::make('EntidadRucRegistro')->label('RUC o registro equivalente')->required(),
                Forms\Components\Radio::make('EntidadRepresentacion')->label('Representación')->options(['PODER_ACTA' => 'Poder por acta', 'ESCRITURA_PUBLICA' => 'Poder por escritura pública', 'MANDATO' => 'Mandato'])->required()->inline(),
                Forms\Components\TextInput::make('BeneficiarioFinalNombres')->label('Nombres y apellidos del beneficiario final')->required(),
            ])->columns(2),
        ])->columns(2)];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::soloUltimaVersion($query)
                ->with(['cliente' => fn (Builder $clientes): Builder => $clientes->withoutGlobalScopes()])
                ->withCount('versiones'))
            ->defaultSort('created_at', 'desc')->columns([
                Tables\Columns\TextColumn::make('NombreCompleto')->label('Cliente')->searchable(['Nombres', 'Apellidos']), Tables\Columns\TextColumn::make('NumeroDocumento')->label('Documento')->searchable(),
                Tables\Columns\TextColumn::make('versiones_count')->label('Versiones')->badge(), Tables\Columns\TextColumn::make('FechaDeclaracion')->label('Fecha')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('Estado')->badge()->formatStateUsing(fn ($state) => EstadoDeclaracion::tryFrom($state)?->label() ?? $state)->color(fn ($state) => $state === 'FINALIZADO' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('situacion_cliente')->label('Situación')->state(fn ($record): string => $record->cliente?->Activo ? 'Activo' : 'De baja')->badge()->color(fn (string $state): string => $state === 'Activo' ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('documento')->label('Declaración')->state(fn ($record) => $record->documentos()->where('TipoDocumento', 'CLIENTE_REGIMEN_GENERAL')->exists())->boolean(),
            ])->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()->visible(fn ($record) => $record->Estado === 'BORRADOR')])->bulkActions([]);
    }

    public static function soloUltimaVersion(Builder $query): Builder
    {
        return $query->whereIn(
            'DeclaracionClienteID',
            DeclaracionCliente::query()
                ->withoutGlobalScope('sede')
                ->selectRaw('MAX(DeclaracionClienteID)')
                ->groupBy('ClienteID'),
        );
    }

    public static function getRelations(): array
    {
        return [RelationManagers\VersionesRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDeclaracionesCliente::route('/'), 'create' => Pages\CreateDeclaracionCliente::route('/create'), 'view' => Pages\ViewDeclaracionCliente::route('/{record}'), 'edit' => Pages\EditDeclaracionCliente::route('/{record}/edit')];
    }
}
