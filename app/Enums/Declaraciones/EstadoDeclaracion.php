<?php

namespace App\Enums\Declaraciones;

enum EstadoDeclaracion: string
{
    case BORRADOR = 'BORRADOR';
    case FINALIZADO = 'FINALIZADO';

    public function label(): string
    {
        return match ($this) {
            self::BORRADOR => 'Borrador',
            self::FINALIZADO => 'Finalizado',
        };
    }

    public static function opciones(): array
    {
        return array_column(array_map(fn (self $item) => ['label' => $item->label(), 'value' => $item->value], self::cases()), 'label', 'value');
    }
}
