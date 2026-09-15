<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Negocio extends JaludReadModel
{
    protected $table = 'Negocio';

    protected $primaryKey = 'NegocioID';

    public $timestamps = false;

    protected $guarded = [];

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class, 'CiudadID', 'CiudadID');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'ZonaID', 'ZonaID');
    }
}
