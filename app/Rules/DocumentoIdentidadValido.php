<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DocumentoIdentidadValido implements ValidationRule
{
    public function __construct(private readonly ?string $tipo) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valor = strtoupper(trim((string) $value));
        $valido = match ($this->tipo) {
            'DNI' => preg_match('/^\d{8}$/', $valor) === 1,
            'PASAPORTE', 'CARNET_EXTRANJERIA' => preg_match('/^[A-Z0-9-]{5,20}$/', $valor) === 1,
            default => preg_match('/^[A-Z0-9.\/-]{3,30}$/', $valor) === 1,
        };
        if (! $valido) { $fail('El número de documento no tiene un formato válido para el tipo seleccionado.'); }
    }
}
