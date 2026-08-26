<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class EnlaceCliente extends Model
{
    protected $table = 'MP_VST_ENLACE_CLIENTE';

    protected $primaryKey = 'ID_MP_VST_ENLACE_CLIENTE';

    public const CREATED_AT = 'CREATED_AT';

    public const UPDATED_AT = 'UPDATED_AT';

    protected $dateFormat = 'Ymd H:i:s';

    protected $fillable = [
        'ID_MP_VST_CLIENTE',
        'NOMBRE',
        'TOKEN_HASH',
        'TOKEN_CIFRADO',
        'ACTIVO',
        'ID_MP_LGN_USUARIO_CREADOR',
        'ULTIMO_ACCESO_AT',
        'CERRADO_AT',
    ];

    protected $hidden = ['TOKEN_HASH', 'TOKEN_CIFRADO'];

    protected function casts(): array
    {
        return [
            'ACTIVO' => 'boolean',
            'ULTIMO_ACCESO_AT' => 'datetime',
            'CERRADO_AT' => 'datetime',
            'CREATED_AT' => 'datetime',
            'UPDATED_AT' => 'datetime',
        ];
    }

    public static function tokenNuevo(): string
    {
        return Str::random(64);
    }

    public static function atributosToken(string $token): array
    {
        return [
            'TOKEN_HASH' => hash('sha256', $token),
            'TOKEN_CIFRADO' => Crypt::encryptString($token),
        ];
    }

    public static function buscarPorToken(string $token): ?self
    {
        return static::query()
            ->with('cliente')
            ->where('TOKEN_HASH', hash('sha256', $token))
            ->where('ACTIVO', true)
            ->whereHas('cliente', fn ($query) => $query->where('ACTIVO', true))
            ->first();
    }

    public function token(): string
    {
        return Crypt::decryptString($this->TOKEN_CIFRADO);
    }

    public function urlPublica(): string
    {
        return route('registro-publico.show', ['token' => $this->token()]);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'ID_MP_VST_CLIENTE', 'ID_MP_VST_CLIENTE');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroCliente::class, 'ID_MP_VST_ENLACE_CLIENTE', 'ID_MP_VST_ENLACE_CLIENTE');
    }
}
