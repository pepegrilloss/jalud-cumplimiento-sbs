<?php

namespace App\Models;

use App\Traits\BelongsToSede;
use App\Traits\ResolvesRosCasoSede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RosAdjunto extends Model
{
    use BelongsToSede, ResolvesRosCasoSede;

    protected $table = 'ros_adjuntos';
    protected $primaryKey = 'RosAdjuntoID';
    protected $fillable = [
        'RosCasoID', 'SedeID', 'RutaArchivo', 'NombreOriginal', 'TipoMime', 'TamanioBytes',
        'Descripcion', 'Categoria', 'HashSha256', 'SubidoPorID',
    ];
    protected $casts = ['TamanioBytes' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $adjunto): void {
            $adjunto->SubidoPorID ??= auth()->id();

            if (!$adjunto->RutaArchivo || !Storage::disk('local')->exists($adjunto->RutaArchivo)) {
                return;
            }

            $ruta = Storage::disk('local')->path($adjunto->RutaArchivo);
            $adjunto->NombreOriginal ??= basename($adjunto->RutaArchivo);
            $adjunto->TipoMime ??= Storage::disk('local')->mimeType($adjunto->RutaArchivo);
            $adjunto->TamanioBytes ??= Storage::disk('local')->size($adjunto->RutaArchivo);
            $adjunto->HashSha256 ??= hash_file('sha256', $ruta) ?: null;
        });
    }

    public function caso(): BelongsTo { return $this->belongsTo(RosCaso::class, 'RosCasoID', 'RosCasoID'); }
    public function subidoPor(): BelongsTo { return $this->belongsTo(User::class, 'SubidoPorID'); }
}
