<?php

namespace App\Enums\Declaraciones;

enum TipoDocumentoIdentidad: string
{
    case DNI = 'DNI';
    case PASAPORTE = 'PASAPORTE';
    case CARNET_EXTRANJERIA = 'CARNET_EXTRANJERIA';
    case OTRO = 'OTRO';

    public function label(): string
    {
        return match ($this) {
            self::DNI => 'DNI',
            self::PASAPORTE => 'Pasaporte',
            self::CARNET_EXTRANJERIA => 'Carné de Extranjería',
            self::OTRO => 'Otro',
        };
    }

    public static function opciones(): array
    {
        return array_column(array_map(fn (self $item) => ['label' => $item->label(), 'value' => $item->value], self::cases()), 'label', 'value');
    }
}
