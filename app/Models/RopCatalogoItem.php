<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public static function opcionesTipoSujetoObligado(): array
    {
        return static::query()->where('Tipo', self::TIPO_SUJETO_OBLIGADO)->where('Activo', true)
            ->orderBy('Orden')->orderBy('Nombre')->get(['Codigo', 'Nombre'])
            ->mapWithKeys(fn (self $item): array => [$item->Codigo => self::etiquetaTipoSujetoObligado($item->Nombre)])
            ->all();
    }

    public static function nombreTipoSujetoObligado(mixed $valor): string
    {
        if (! filled($valor)) {
            return '';
        }

        try {
            $item = static::query()->where('Tipo', self::TIPO_SUJETO_OBLIGADO)
                ->where(fn ($query) => $query->where('Codigo', $valor)->orWhere('Nombre', $valor))
                ->first(['Nombre']);

            return $item
                ? self::etiquetaTipoSujetoObligado($item->Nombre)
                : (string) $valor;
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    private static function etiquetaTipoSujetoObligado(string $nombre): string
    {
        $normalizado = Str::ascii(mb_strtolower($nombre));

        if (preg_match('/empresa(s)? (del|de) sistema financiero|empresa(s)? financiera(s)?/', $normalizado)) {
            return 'Casa de préstamos';
        }

        return $nombre;
    }
}
