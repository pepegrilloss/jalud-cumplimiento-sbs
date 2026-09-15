<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RopAuditoria extends Model
{
    use BelongsToSede;

    protected $table = 'rop_auditorias';

    protected $primaryKey = 'RopAuditoriaID';

    public $timestamps = false;

    protected $guarded = ['RopAuditoriaID'];

    protected $casts = ['ValoresAnteriores' => 'array', 'ValoresNuevos' => 'array', 'created_at' => 'datetime'];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RopRegistro::class, 'RopRegistroID', 'RopRegistroID');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(RopPeriodo::class, 'RopPeriodoID', 'RopPeriodoID');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID');
    }
}
