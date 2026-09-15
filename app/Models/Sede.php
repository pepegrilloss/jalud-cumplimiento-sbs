<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends JaludReadModel
{
    protected $table = 'Sede';

    protected $primaryKey = 'SedeID';

    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'Codigo',
        'Activo',
        'FechaCreacion',
        'FechaModificacion',
    ];

    protected $casts = [
        'Activo' => 'boolean',
        'FechaCreacion' => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function scopeOperativas(Builder $query): Builder
    {
        return $query
            ->where('Activo', true)
            ->whereRaw('LOWER(Nombre) NOT LIKE ?', ['%gerencia%']);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'SedeID', 'SedeID');
    }
}
