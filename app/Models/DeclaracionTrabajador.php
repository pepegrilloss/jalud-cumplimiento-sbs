<?php

namespace App\Models;

use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class DeclaracionTrabajador extends Model
{
    use BelongsToSede;

    protected $table = 'dj_declaraciones_trabajador';
    protected $primaryKey = 'DeclaracionTrabajadorID';
    protected $guarded = ['DeclaracionTrabajadorID'];
    protected $casts = [
        'FechaContratacion' => 'date', 'FechaNacimiento' => 'date', 'FechaDeclaracion' => 'date',
        'FinalizadoEn' => 'datetime', 'TieneAntecedentesPoliciales' => 'boolean', 'TieneAntecedentesPenales' => 'boolean',
        'RemuneracionBrutaMensual' => 'decimal:2', 'IngresoEjercicioIndividual' => 'decimal:2', 'OtrosIngresosMensuales' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $declaracion): void {
            $declaracion->sanitizarCondicionales();
            $declaracion->CreadoPorID ??= auth()->id();
            $declaracion->ActualizadoPorID ??= auth()->id();
            $declaracion->Estado ??= EstadoDeclaracion::BORRADOR->value;
        });
        static::updating(function (self $declaracion): void {
            if ($declaracion->getOriginal('Estado') === EstadoDeclaracion::FINALIZADO->value) {
                throw ValidationException::withMessages(['Estado' => 'Una declaración finalizada es inmutable. Cree una nueva versión.']);
            }
            $declaracion->sanitizarCondicionales();
            $declaracion->ActualizadoPorID = auth()->id();
        });
    }

    public function trabajador(): BelongsTo { return $this->belongsTo(Trabajador::class, 'TrabajadorID', 'TrabajadorID'); }
    public function estudios(): HasMany { return $this->hasMany(Estudio::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderBy('Orden'); }
    public function educacionEscolar(): HasMany { return $this->hasMany(EducacionEscolar::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderBy('Orden'); }
    public function otrosEstudios(): HasMany { return $this->hasMany(OtroEstudio::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderBy('Orden'); }
    public function experienciasLaborales(): HasMany { return $this->hasMany(ExperienciaLaboral::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderByDesc('FechaInicio'); }
    public function capacitaciones(): HasMany { return $this->hasMany(Capacitacion::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderByDesc('FechaCapacitacion'); }
    public function bienesInmuebles(): HasMany { return $this->hasMany(BienInmueble::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderBy('Orden'); }
    public function vehiculos(): HasMany { return $this->hasMany(Vehiculo::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderBy('Orden'); }
    public function otrosBienes(): HasMany { return $this->hasMany(OtroBien::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderBy('Orden'); }
    public function inversiones(): HasMany { return $this->hasMany(Inversion::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderBy('Orden'); }
    public function obligaciones(): HasMany { return $this->hasMany(Obligacion::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')->orderBy('Orden'); }
    public function documentos(): HasMany
    {
        return $this->hasMany(DeclaracionDocumento::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID')
            ->withoutGlobalScopes();
    }
    public function getNombreCompletoAttribute(): string { return trim("{$this->Nombres} {$this->ApellidoPaterno} {$this->ApellidoMaterno}"); }
    public function getTotalIngresosAttribute(): float { return (float) $this->RemuneracionBrutaMensual + (float) $this->IngresoEjercicioIndividual + (float) $this->OtrosIngresosMensuales; }

    public function sanitizarCondicionales(): void
    {
        if ($this->TipoDocumento !== 'OTRO') { $this->TipoDocumentoOtro = null; }
        if (!in_array($this->EstadoCivil, ['CASADO', 'CONVIVIENTE'], true)) { $this->ConyugeNombres = null; $this->ConyugeApellidos = null; }
        if (!$this->TieneAntecedentesPoliciales) { $this->DetalleAntecedentesPoliciales = null; }
        if (!$this->TieneAntecedentesPenales) { $this->DetalleAntecedentesPenales = null; }
        if ($this->CondicionVivienda !== 'OTRA') { $this->CondicionViviendaOtra = null; }
        if ((float) $this->OtrosIngresosMensuales <= 0) { $this->DetalleOtrosIngresos = null; }
    }
}
