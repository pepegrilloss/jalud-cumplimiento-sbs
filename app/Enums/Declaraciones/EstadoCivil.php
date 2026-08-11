<?php

namespace App\Enums\Declaraciones;

enum EstadoCivil: string
{
    case SOLTERO = 'SOLTERO';
    case CASADO = 'CASADO';
    case VIUDO = 'VIUDO';
    case DIVORCIADO = 'DIVORCIADO';
    case CONVIVIENTE = 'CONVIVIENTE';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }

    public static function opciones(): array
    {
        return array_column(array_map(fn (self $item) => ['label' => $item->label(), 'value' => $item->value], self::cases()), 'label', 'value');
    }
}
