<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use App\Traits\ResolvesRosCasoSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RosPersona extends Model
{
    use BelongsToSede, ResolvesRosCasoSede;

    protected $table = 'ros_personas';

    protected $primaryKey = 'RosPersonaID';

    protected $fillable = [
        'RosCasoID', 'SedeID', 'ClienteID', 'TipoPersona', 'CondicionParticipacion',
        'ApellidoPaterno', 'ApellidoMaterno', 'Nombres', 'RazonSocial', 'TipoDocumento',
        'NumeroDocumento', 'PaisEmisionDocumento', 'FechaNacimiento', 'Nacionalidad', 'EsPep',
        'ProfesionOcupacion', 'ActividadEconomica', 'Domicilio', 'PaisDomicilio',
        'DepartamentoDomicilio', 'ProvinciaDomicilio', 'DistritoDomicilio', 'UrbanizacionSector',
        'TipoVia', 'NombreVia', 'NumeroDomicilio', 'Telefono', 'Telefono2', 'Telefono3', 'Correo',
        'EstadoCivil', 'ConyugeApellidoPaterno', 'ConyugeApellidoMaterno', 'ConyugeNombres',
        'CondicionActividad', 'Empleador', 'TelefonoLaboral', 'IngresoMensual', 'MonedaIngreso',
        'PartidaElectronica', 'SedeRegistral', 'ObjetoSocial', 'IngresoAnual', 'MonedaIngresoAnual',
        'RelacionEmpresa', 'RelacionEmpresaOtro', 'VinculadoActualmente', 'MotivoDesvinculacion',
        'FechaDesvinculacion', 'RosPersonaRepresentadaID',
        'RepApellidoPaterno', 'RepApellidoMaterno', 'RepNombres', 'RepFechaNacimiento',
        'RepNacionalidad', 'RepProfesionOcupacion', 'RepTipoDocumento', 'RepNumeroDocumento',
        'RepPaisEmisionDocumento', 'RepPaisDomicilio', 'RepDepartamentoDomicilio',
        'RepProvinciaDomicilio', 'RepDistritoDomicilio', 'RepUrbanizacionSector', 'RepTipoVia',
        'RepNombreVia', 'RepNumeroDomicilio', 'RepTelefono', 'RepTelefono2', 'RepTelefono3',
        'RepCorreo', 'RepEstadoCivil', 'RepConyugeApellidoPaterno', 'RepConyugeApellidoMaterno',
        'RepConyugeNombres',
    ];

    protected $casts = [
        'FechaNacimiento' => 'date',
        'EsPep' => 'boolean',
        'IngresoMensual' => 'decimal:2',
        'IngresoAnual' => 'decimal:2',
        'VinculadoActualmente' => 'boolean',
        'FechaDesvinculacion' => 'date',
        'RepFechaNacimiento' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $persona): void {
            $persona->EsPep ??= false;
        });
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(RosCaso::class, 'RosCasoID', 'RosCasoID');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'ClienteID', 'ClienteID');
    }

    public function personaRepresentada(): BelongsTo
    {
        return $this->belongsTo(self::class, 'RosPersonaRepresentadaID', 'RosPersonaID');
    }

    public function reportesPrevios(): HasMany
    {
        return $this->hasMany(RosReportePrevio::class, 'RosPersonaID', 'RosPersonaID');
    }
}
