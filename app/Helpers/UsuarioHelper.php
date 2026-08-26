<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class UsuarioHelper
{
    public static function areaId(): ?int
    {
        $userId = auth()->id();
        if (!$userId) return null;

        $registro = DB::table('MP_LGN_USUARIOS_REGISTRADOS')
            ->where('ID_MP_LGN_USUARIO', $userId)
            ->first();

        return $registro?->ID_MP_LGN_AREA;
    }

    public static function esTI(): bool
    {
        return self::areaId() === 1;
    }

    public static function esVisitante(): bool
    {
        return self::areaId() === 11;
    }

    public static function esGarita(): bool
    {
        $usuario = auth()->user()?->name;

        return is_string($usuario) && str_starts_with(strtoupper(trim($usuario)), 'GARITA');
    }

    public static function plantaEmpaqueId(): ?int
    {
        $planta = auth()->user()?->ID_MLP_PLANTA_EMPAQUE;

        return $planta ? (int) $planta : null;
    }

    public static function esComercial(): bool
    {
        return self::areaId() === 12;
    }

    public static function puedeEditarVisita(): bool
    {
        return self::esTI() || self::esComercial();
    }

    public static function puedeAprobarArea(int $areaId): bool
    {
        if (self::esTI()) return true;
        return self::areaId() === $areaId;
    }
}
