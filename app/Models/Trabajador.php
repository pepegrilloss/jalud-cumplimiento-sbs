<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trabajador extends Model
{
    use BelongsToSede;

    protected $table = 'dj_trabajadores';

    protected $primaryKey = 'TrabajadorID';

    protected $guarded = ['TrabajadorID'];

    protected $casts = ['Activo' => 'boolean'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID');
    }

    public function declaraciones(): HasMany
    {
        return $this->hasMany(DeclaracionTrabajador::class, 'TrabajadorID', 'TrabajadorID');
    }

    public function archivosAdjuntos(): HasMany
    {
        return $this->hasMany(TrabajadorArchivo::class, 'TrabajadorID', 'TrabajadorID');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->Nombres} {$this->ApellidoPaterno} {$this->ApellidoMaterno}");
    }
}
