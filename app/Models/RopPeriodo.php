<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RopPeriodo extends Model
{
    use BelongsToSede;

    public const ESTADO_ABIERTO = 'ABIERTO';

    public const ESTADO_CERRADO = 'CERRADO';

    protected $table = 'rop_periodos';

    protected $primaryKey = 'RopPeriodoID';

    protected $guarded = ['RopPeriodoID'];

    protected $casts = ['Periodo' => 'date', 'CerradoEn' => 'datetime'];

    public function registros(): HasMany
    {
        return $this->hasMany(RopRegistro::class, 'RopPeriodoID', 'RopPeriodoID');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(RopAuditoria::class, 'RopPeriodoID', 'RopPeriodoID');
    }
}
