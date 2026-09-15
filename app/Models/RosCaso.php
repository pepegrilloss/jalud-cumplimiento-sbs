<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RosCaso extends Model
{
    use BelongsToSede;

    public bool $permiteCambioDeEstado = false;

    public const ESTADO_BORRADOR = 'BORRADOR';

    public const ESTADO_EVALUACION = 'EN_EVALUACION';

    public const ESTADO_DESCARTADO = 'DESCARTADO';

    public const ESTADO_APROBADO = 'APROBADO_PARA_ROS';

    public const ESTADO_REPORTADO = 'REPORTADO';

    public const ORIGEN_ROP = 'ROP';

    public const ORIGEN_DIRECTO = 'DIRECTO';

    public const PERSONA_CLIENTE = 'CLIENTE';

    public const PERSONA_TRABAJADOR = 'TRABAJADOR';

    protected $table = 'ros_casos';

    protected $primaryKey = 'RosCasoID';

    protected $fillable = [
        'SedeID', 'ZonaID', 'ClienteID', 'TrabajadorID', 'CreditoID', 'PagoID', 'RopRegistroID', 'OrigenRos',
        'TipoPersonaOrigen', 'CodigoInterno', 'Estado',
        'ClaseReporte', 'NumeroReporte', 'FechaReporte', 'NumeroReporteAnterior', 'FechaReporteAnterior',
        'EnteSupervisor', 'TipoSujetoObligado', 'CodigoEmpresa', 'CodigoOficialCumplimiento',
        'OficinaInformante', 'OficinaOperacion', 'DepartamentoOperacion', 'ProvinciaOperacion',
        'DistritoOperacion',
        'FechaOperacionDesde', 'FechaOperacionHasta', 'MontoTotal', 'Moneda', 'DelitoPrecedente',
        'Alcance', 'PaisesRelacionados', 'SectorEconomico', 'ActividadEconomica',
        'DescripcionHechos', 'RelacionPersonas', 'RelacionProductosServicios', 'CaracteristicasSospechosas',
        'ExplicacionesJustificaciones', 'RelacionOtrasOperaciones', 'InformacionExcluida',
        'CambiosReporteAnterior', 'FuentesConsultadas', 'DocumentacionComplementaria', 'FechaCalificadoRos', 'CalificadoPorID',
        'FechaReportado', 'NumeroEnvioRosel', 'FechaEnvioRosel', 'ConstanciaRoselRuta',
        'MotivoDescarte', 'Bloqueado', 'VersionNormativa',
    ];

    protected $casts = [
        'FechaReporte' => 'date',
        'FechaReporteAnterior' => 'date',
        'FechaOperacionDesde' => 'date',
        'FechaOperacionHasta' => 'date',
        'FechaCalificadoRos' => 'datetime',
        'FechaReportado' => 'datetime',
        'FechaEnvioRosel' => 'date',
        'MontoTotal' => 'decimal:2',
        'Bloqueado' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $caso): void {
            $usuario = auth()->user();

            if ($usuario && ! $usuario->can('ver_todos_los_casos_sbs')) {
                $caso->SedeID = $usuario->getEffectiveSedeId();
            }

            if (empty($caso->SedeID)) {
                throw ValidationException::withMessages([
                    'SedeID' => 'Debe seleccionar una sede para el caso ROS.',
                ]);
            }

            $caso->validarRelacionesDeSede();
        });

        static::creating(function (self $caso): void {
            $caso->OrigenRos = $caso->RopRegistroID ? self::ORIGEN_ROP : self::ORIGEN_DIRECTO;
            $caso->TipoPersonaOrigen = $caso->RopRegistroID
                ? self::PERSONA_CLIENTE
                : ($caso->TipoPersonaOrigen ?: self::PERSONA_CLIENTE);
            $caso->CodigoInterno ??= 'ROS-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            $caso->CreadoPorID = auth()->id();
            $caso->ActualizadoPorID = auth()->id();
        });

        static::updating(function (self $caso): void {
            if (($caso->getOriginal('Estado') === self::ESTADO_REPORTADO || $caso->getOriginal('Bloqueado'))
                && ! $caso->permiteCambioDeEstado) {
                throw ValidationException::withMessages([
                    'Estado' => 'Un caso reportado está bloqueado y no admite modificaciones.',
                ]);
            }

            if ($caso->isDirty('Estado') && ! $caso->permiteCambioDeEstado) {
                throw ValidationException::withMessages([
                    'Estado' => 'Use las acciones del flujo ROS para cambiar el estado.',
                ]);
            }

            $caso->ActualizadoPorID = auth()->id();
        });
    }

    public static function estados(): array
    {
        return [
            self::ESTADO_BORRADOR => 'Borrador',
            self::ESTADO_EVALUACION => 'En evaluacion',
            self::ESTADO_DESCARTADO => 'Descartado',
            self::ESTADO_APROBADO => 'Aprobado para ROS',
            self::ESTADO_REPORTADO => 'Reportado',
        ];
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'SedeID', 'SedeID');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'ZonaID', 'ZonaID');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'ClienteID', 'ClienteID');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'TrabajadorID', 'TrabajadorID');
    }

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class, 'CreditoID', 'CreditoID');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'PagoID', 'PagoID');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'CreadoPorID');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ActualizadoPorID');
    }

    public function personas(): HasMany
    {
        return $this->hasMany(RosPersona::class, 'RosCasoID', 'RosCasoID');
    }

    public function operaciones(): HasMany
    {
        return $this->hasMany(RosOperacion::class, 'RosCasoID', 'RosCasoID');
    }

    public function senalesAlerta(): HasMany
    {
        return $this->hasMany(RosSenalAlerta::class, 'RosCasoID', 'RosCasoID');
    }

    public function tipologias(): HasMany
    {
        return $this->hasMany(RosTipologia::class, 'RosCasoID', 'RosCasoID');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(RosAdjunto::class, 'RosCasoID', 'RosCasoID');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(RosAuditoria::class, 'RosCasoID', 'RosCasoID');
    }

    public function reportesPrevios(): HasMany
    {
        return $this->hasMany(RosReportePrevio::class, 'RosCasoID', 'RosCasoID');
    }

    public function transiciones(): HasMany
    {
        return $this->hasMany(RosTransicion::class, 'RosCasoID', 'RosCasoID');
    }

    public function calificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'CalificadoPorID');
    }

    public function registroRop(): BelongsTo
    {
        return $this->belongsTo(RopRegistro::class, 'RopRegistroID', 'RopRegistroID');
    }

    private function validarRelacionesDeSede(): void
    {
        foreach ([
            'ZonaID' => [Zona::class, 'ZonaID'],
            'ClienteID' => [Cliente::class, 'ClienteID'],
            'TrabajadorID' => [Trabajador::class, 'TrabajadorID'],
            'CreditoID' => [Credito::class, 'CreditoID'],
            'PagoID' => [Pago::class, 'PagoID'],
        ] as $atributo => [$modelo, $llave]) {
            if (empty($this->{$atributo})) {
                continue;
            }

            $perteneceASede = $modelo::withoutGlobalScope('sede')
                ->where($llave, $this->{$atributo})
                ->where('SedeID', $this->SedeID)
                ->exists();

            if (! $perteneceASede) {
                throw ValidationException::withMessages([
                    $atributo => 'El registro seleccionado pertenece a una sede distinta al caso ROS.',
                ]);
            }
        }
    }
}
