<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Local, auditable compliance snapshot of a Jalud client. */
class ClienteCumplimiento extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToSede;

    protected $table = 'dj_clientes';

    protected $primaryKey = 'ClienteID';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = ['ClienteID'];

    protected $casts = ['Activo' => 'boolean', 'SincronizadoEn' => 'datetime'];

    public function declaraciones(): HasMany
    {
        return $this->hasMany(DeclaracionCliente::class, 'ClienteID', 'ClienteID');
    }

    /** Refreshes descriptive data only; the local audited status is preserved. */
    public static function sincronizarDesdeJalud(Cliente $origen): self
    {
        $cliente = self::withoutGlobalScopes()->firstOrNew(['ClienteID' => $origen->getKey()]);
        $esNuevo = ! $cliente->exists;

        // ClienteID is the immutable source identifier from Jalud, not an auto-increment key.
        $cliente->ClienteID = $origen->getKey();

        $cliente->fill([
            'SedeID' => $origen->SedeID,
            'DNI' => $origen->DNI,
            'NombresApellidos' => $origen->NombresApellidos,
            'ApellidoPaterno' => $origen->ApellidoPaterno,
            'ApellidoMaterno' => $origen->ApellidoMaterno,
            'Nombres' => $origen->Nombres,
            'Domicilio' => $origen->Domicilio,
            'ConyugeNombresApellidos' => $origen->ConyugeNombresApellidos,
            'SincronizadoEn' => now(),
        ]);
        $cliente->Activo = $esNuevo ? (bool) $origen->Activo : $cliente->Activo;
        $cliente->save();

        return $cliente;
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->NombresApellidos ?: "{$this->ApellidoPaterno} {$this->ApellidoMaterno} {$this->Nombres}");
    }
}
