<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosCatalogoItem extends Model
{
    protected $table = 'ros_catalogo_items';
    protected $primaryKey = 'RosCatalogoItemID';
    protected $guarded = ['RosCatalogoItemID'];
    protected $casts = ['Activo' => 'boolean', 'Orden' => 'integer'];

    public static function opciones(string $tipo): array
    {
        return static::query()
            ->where('Tipo', $tipo)
            ->where('Activo', true)
            ->orderBy('Orden')
            ->pluck('Nombre', 'Codigo')
            ->all();
    }
}
