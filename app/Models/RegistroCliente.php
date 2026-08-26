<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RegistroCliente extends Model
{
    protected $table = 'MP_VST_REGISTRO_CLIENTE';

    protected $primaryKey = 'ID_MP_VST_REGISTRO_CLIENTE';

    public const CREATED_AT = 'CREATED_AT';

    public const UPDATED_AT = 'UPDATED_AT';

    protected $dateFormat = 'Ymd H:i:s';

    protected $fillable = [
        'ID_MP_VST_ENLACE_CLIENTE',
        'SUBMISSION_KEY',
        'NOMBRES',
        'DNI',
        'DNI_PATH',
        'IP',
        'USER_AGENT',
        'INGRESO_AT',
        'SALIDA_AT',
    ];

    protected $hidden = ['SUBMISSION_KEY', 'DNI_PATH', 'IP', 'USER_AGENT'];

    protected function casts(): array
    {
        return [
            'CREATED_AT' => 'datetime',
            'UPDATED_AT' => 'datetime',
            'INGRESO_AT' => 'datetime',
            'SALIDA_AT' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (self $registro): void {
            Storage::disk('local')->delete($registro->DNI_PATH);
        });
    }

    public function enlace(): BelongsTo
    {
        return $this->belongsTo(EnlaceCliente::class, 'ID_MP_VST_ENLACE_CLIENTE', 'ID_MP_VST_ENLACE_CLIENTE');
    }
}
