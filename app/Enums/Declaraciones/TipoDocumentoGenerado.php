<?php

namespace App\Enums\Declaraciones;

enum TipoDocumentoGenerado: string
{
    case ANEXO_1 = 'ANEXO_1';
    case ANEXO_2 = 'ANEXO_2';
    case ANEXO_3 = 'ANEXO_3';
    case CLIENTE_REGIMEN_GENERAL = 'CLIENTE_REGIMEN_GENERAL';

    public function nombreArchivo(): string
    {
        return match ($this) {
            self::ANEXO_1 => 'Anexo 1 - DJ TRABAJADORES.pdf',
            self::ANEXO_2 => 'Anexo 2 - DJ ANTECEDENTES LABORALES.pdf',
            self::ANEXO_3 => 'Anexo 3 - DJ INFORMACION PATRIMONIAL.pdf',
            self::CLIENTE_REGIMEN_GENERAL => 'Declaracion Jurada de conocimiento del cliente.pdf',
        };
    }
}
