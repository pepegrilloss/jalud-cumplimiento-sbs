<?php

namespace App\Enums\Declaraciones;

enum CodigoOcupacion: string
{
    case AMA_CASA = '001';
    case DESEMPLEADO = '002';
    case EMPLEADO = '003';
    case EMPLEADOR = '004';
    case ESTUDIANTE = '005';
    case JUBILADO = '006';
    case FF_AA_CLERO = '007';
    case OBRERO = '008';
    case TRABAJADOR_HOGAR = '009';
    case INDEPENDIENTE = '010';
    case NO_DECLARA = '099';

    public function label(): string
    {
        return match ($this) {
            self::AMA_CASA => 'Ama de casa', self::DESEMPLEADO => 'Desempleado', self::EMPLEADO => 'Empleado',
            self::EMPLEADOR => 'Empleador(a)', self::ESTUDIANTE => 'Estudiante', self::JUBILADO => 'Jubilado(a)',
            self::FF_AA_CLERO => 'Miembro de las fuerzas armadas / Miembro del clero', self::OBRERO => 'Obrero(a)',
            self::TRABAJADOR_HOGAR => 'Trabajador(a) del hogar', self::INDEPENDIENTE => 'Trabajador(a) independiente',
            self::NO_DECLARA => 'No declara',
        };
    }

    public static function opciones(): array
    {
        return array_column(array_map(fn (self $item) => ['label' => "{$item->value} - {$item->label()}", 'value' => $item->value], self::cases()), 'label', 'value');
    }
}
