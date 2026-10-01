<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RopAdjunto extends Model
{
    use BelongsToSede;

    protected $table = 'rop_adjuntos';
    protected $primaryKey = 'RopAdjuntoID';
    protected $fillable = [
        'RopRegistroID', 'SedeID', 'RutaArchivo', 'NombreOriginal', 'TipoMime',
        'TamanioBytes', 'Descripcion', 'Categoria', 'HashSha256', 'SubidoPorID',
    ];
    protected $casts = ['TamanioBytes' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $adjunto): void {
            $registro = RopRegistro::withoutGlobalScopes()->findOrFail($adjunto->RopRegistroID);
            $adjunto->SedeID = $registro->SedeID;
            $adjunto->SubidoPorID ??= auth()->id();

            if ($adjunto->RutaArchivo && Storage::disk('local')->exists($adjunto->RutaArchivo)) {
                $ruta = Storage::disk('local')->path($adjunto->RutaArchivo);
                $adjunto->NombreOriginal ??= basename($adjunto->RutaArchivo);
                $adjunto->TipoMime ??= Storage::disk('local')->mimeType($adjunto->RutaArchivo);
                $adjunto->TamanioBytes ??= Storage::disk('local')->size($adjunto->RutaArchivo);
                $adjunto->HashSha256 ??= hash_file('sha256', $ruta) ?: null;
            }
        });
    }

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RopRegistro::class, 'RopRegistroID', 'RopRegistroID');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'SubidoPorID');
    }
}
