<?php

namespace App\Support;

class Pii
{
    public static function maskDni(?string $dni): string
    {
        $dni = trim((string) $dni);

        if ($dni === '') {
            return '-';
        }

        $length = strlen($dni);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($dni, 0, 2)
            . str_repeat('*', max(0, $length - 4))
            . substr($dni, -2);
    }
}
