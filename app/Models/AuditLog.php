<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'MP_VST_AUDITORIA';
    protected $primaryKey = 'ID_MP_VST_AUDITORIA';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    const CREATED_AT = 'CREATED_AT';

    /**
     * SQL Server production runs with dmy dateformat. Use an unambiguous format.
     */
    protected $dateFormat = 'Ymd H:i:s';

    protected $fillable = [
        'ID_MP_LGN_USUARIO',
        'USUARIO',
        'AREA',
        'EVENTO',
        'ACCION',
        'MODELO',
        'TABLA',
        'ID_REGISTRO',
        'ID_MP_VST_VISITA',
        'EMPRESA',
        'RESUMEN',
        'VALORES_ANTERIORES',
        'VALORES_NUEVOS',
        'METADATA',
        'IP',
        'METODO',
        'URL',
        'USER_AGENT',
        'CREATED_AT',
    ];

    protected function casts(): array
    {
        return [
            'ID_MP_VST_AUDITORIA' => 'integer',
            'ID_MP_LGN_USUARIO' => 'integer',
            'ID_MP_VST_VISITA' => 'integer',
            'VALORES_ANTERIORES' => 'array',
            'VALORES_NUEVOS' => 'array',
            'METADATA' => 'array',
            'CREATED_AT' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'ID_MP_LGN_USUARIO', 'ID_MP_LGN_USUARIO');
    }

}
