<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeclaracionDocumento extends Model
{
    use BelongsToSede;
    protected $table = 'dj_documentos';
    protected $primaryKey = 'DeclaracionDocumentoID';
    protected $guarded = ['DeclaracionDocumentoID'];
    protected $casts = ['GeneradoEn' => 'datetime'];
    public function declaracionTrabajador(): BelongsTo { return $this->belongsTo(DeclaracionTrabajador::class, 'DeclaracionTrabajadorID', 'DeclaracionTrabajadorID'); }
    public function declaracionCliente(): BelongsTo { return $this->belongsTo(DeclaracionCliente::class, 'DeclaracionClienteID', 'DeclaracionClienteID'); }
    public function generadoPor(): BelongsTo { return $this->belongsTo(User::class, 'GeneradoPorID'); }
}
