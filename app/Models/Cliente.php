<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Cliente extends Model
{
    protected $table = 'MP_VST_CLIENTE';

    protected $primaryKey = 'ID_MP_VST_CLIENTE';

    public const CREATED_AT = 'CREATED_AT';

    public const UPDATED_AT = 'UPDATED_AT';

    protected $dateFormat = 'Ymd H:i:s';

    protected $fillable = [
        'RAZON_SOCIAL',
        'ACTIVO',
        'ID_MP_LGN_USUARIO_CREADOR',
    ];

    protected function casts(): array
    {
        return [
            'ACTIVO' => 'boolean',
            'CREATED_AT' => 'datetime',
            'UPDATED_AT' => 'datetime',
        ];
    }

    public function enlaces(): HasMany
    {
        return $this->hasMany(EnlaceCliente::class, 'ID_MP_VST_CLIENTE', 'ID_MP_VST_CLIENTE');
    }

    public function registros(): HasManyThrough
    {
        return $this->hasManyThrough(
            RegistroCliente::class,
            EnlaceCliente::class,
            'ID_MP_VST_CLIENTE',
            'ID_MP_VST_ENLACE_CLIENTE',
            'ID_MP_VST_CLIENTE',
            'ID_MP_VST_ENLACE_CLIENTE',
        );
    }
}
