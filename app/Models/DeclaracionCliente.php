<?php

namespace App\Models;

use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class DeclaracionCliente extends Model
{
    use BelongsToSede;
    protected $table = 'dj_declaraciones_cliente';
    protected $primaryKey = 'DeclaracionClienteID';
    protected $guarded = ['DeclaracionClienteID'];
    protected $casts = ['FechaDeclaracion' => 'date', 'FinalizadoEn' => 'datetime', 'EsParientePep' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $d): void { $d->sanitizarCondicionales(); $d->CreadoPorID ??= auth()->id(); $d->ActualizadoPorID ??= auth()->id(); $d->Estado ??= EstadoDeclaracion::BORRADOR->value; });
        static::updating(function (self $d): void {
            if ($d->getOriginal('Estado') === EstadoDeclaracion::FINALIZADO->value) {
                throw ValidationException::withMessages(['Estado' => 'Una declaración finalizada es inmutable. Cree una nueva versión.']);
            }
            $d->sanitizarCondicionales();
            $d->ActualizadoPorID = auth()->id();
        });
    }

    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class, 'ClienteID', 'ClienteID'); }
    public function pepFamiliares(): HasMany { return $this->hasMany(PepFamiliar::class, 'DeclaracionClienteID', 'DeclaracionClienteID')->orderBy('Orden'); }
    public function pepRelacionados(): HasMany { return $this->hasMany(PepRelacionado::class, 'DeclaracionClienteID', 'DeclaracionClienteID')->orderBy('Orden'); }
    public function documentos(): HasMany
    {
        return $this->hasMany(DeclaracionDocumento::class, 'DeclaracionClienteID', 'DeclaracionClienteID')
            ->withoutGlobalScopes();
    }
    public function getNombreCompletoAttribute(): string { return trim("{$this->Nombres} {$this->Apellidos}"); }

    public function sanitizarCondicionales(): void
    {
        if ($this->TipoDocumento !== 'OTRO') { $this->TipoDocumentoOtro = null; }
        if ($this->EstadoCivil !== 'CASADO') { $this->ConyugeConviviente = null; }
        if (!in_array($this->EstadoPep, ['SI_SOY', 'SI_HE_SIDO'], true)) { $this->PepCargo = null; $this->PepInstitucion = null; }
        if (!$this->EsParientePep) { /* Los detalles relacionados se eliminan desde el servicio. */ }
        if ($this->TipoBeneficiario !== 'TERCERO_NATURAL') {
            foreach (['TerceroNombresApellidos','TerceroTipoDocumento','TerceroTipoDocumentoOtro','TerceroNumeroDocumento','TerceroRepresentacion','TerceroEstadoPep','TerceroPepCargo','TerceroPepInstitucion'] as $campo) { $this->{$campo}=null; }
        } elseif ($this->TerceroTipoDocumento !== 'OTRO') { $this->TerceroTipoDocumentoOtro = null; }
        if (!in_array($this->TerceroEstadoPep, ['SI_ES', 'SI_HA_SIDO'], true)) { $this->TerceroPepCargo = null; $this->TerceroPepInstitucion = null; }
        if (!in_array($this->TipoBeneficiario, ['PERSONA_JURIDICA', 'ENTE_JURIDICO'], true)) {
            foreach (['EntidadRazonSocial','EntidadRucRegistro','EntidadRepresentacion','BeneficiarioFinalNombres'] as $campo) { $this->{$campo}=null; }
        }
    }
}
