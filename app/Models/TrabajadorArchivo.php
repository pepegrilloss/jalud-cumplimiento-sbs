<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TrabajadorArchivo extends Model
{
    use BelongsToSede;

    protected $table = 'dj_trabajador_archivos';

    protected $primaryKey = 'TrabajadorArchivoID';

    protected $guarded = ['TrabajadorArchivoID'];

    protected static function booted(): void
    {
        static::creating(function (self $archivo): void {
            $trabajador = Trabajador::withoutGlobalScopes()->findOrFail($archivo->TrabajadorID);
            $archivo->SedeID = $trabajador->SedeID;
            $archivo->SubidoPorID ??= auth()->id();
        });

        static::deleted(function (self $archivo): void {
            if ($archivo->RutaArchivo) {
                Storage::disk('local')->delete($archivo->RutaArchivo);
            }
        });
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'TrabajadorID', 'TrabajadorID');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'SubidoPorID');
    }
}
