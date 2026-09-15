<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RopCatalogoItem extends Model
{
    public const TIPO_SUJETO_OBLIGADO = 'TIPO_SUJETO_OBLIGADO';

    public const ACTIVIDAD_ECONOMICA = 'ACTIVIDAD_ECONOMICA';

    public const TIPO_VIA = 'TIPO_VIA';

    protected $table = 'rop_catalogo_items';

    protected $primaryKey = 'RopCatalogoItemID';

    protected $guarded = ['RopCatalogoItemID'];

    protected $casts = ['Activo' => 'boolean'];

    public static function tipos(): array
    {
        return [
            self::TIPO_SUJETO_OBLIGADO => 'Tabla N.° 1 - Tipo de sujeto obligado',
            self::ACTIVIDAD_ECONOMICA => 'Tabla N.° 2 - Sectores y actividades económicas',
            self::TIPO_VIA => 'Tabla N.° 3 - Vías',
        ];
    }

    public static function opciones(string $tipo): array
    {
        return static::query()->where('Tipo', $tipo)->where('Activo', true)
            ->orderBy('Orden')->orderBy('Nombre')->pluck('Nombre', 'Codigo')->all();
    }
}
