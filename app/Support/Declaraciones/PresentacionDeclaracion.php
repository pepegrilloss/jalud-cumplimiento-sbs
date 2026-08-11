<?php

namespace App\Support\Declaraciones;

use App\Enums\Declaraciones\EstadoCivil;
use App\Enums\Declaraciones\TipoDocumentoIdentidad;

final class PresentacionDeclaracion
{
    private const ETIQUETAS = [
        'DOCTORADO'=>'Doctorado', 'MAESTRIA'=>'Maestría', 'DIPLOMADO'=>'Diplomado o especialización',
        'LICENCIATURA'=>'Licenciatura o título profesional', 'BACHILLERATO'=>'Bachillerato', 'TECNICO'=>'Estudios técnicos',
        'TITULADO'=>'Titulado', 'EN_TRAMITE'=>'En trámite', 'EGRESADO'=>'Egresado', 'ESTUDIANDO'=>'Cursando actualmente',
        'PRIMARIA'=>'Primaria', 'SECUNDARIA'=>'Secundaria', 'IDIOMA'=>'Idioma', 'COMPUTACION'=>'Computación', 'OTRO'=>'Otro',
        'PROPIA'=>'Casa propia', 'PADRES'=>'De los padres', 'SOCIEDAD_CONYUGAL'=>'Sociedad conyugal',
        'CONCUBINOS'=>'De los convivientes', 'ALQUILADA'=>'Alquilada', 'CEDIDA'=>'Cedida en uso',
        'ENTIDAD_FINANCIERA'=>'Entidad financiera', 'PERSONA_NATURAL'=>'Persona natural',
        'ESCRITURA_PUBLICA'=>'Poder por escritura pública', 'PODER_ACTA'=>'Poder por acta', 'MANDATO'=>'Mandato',
        'YO_MISMO'=>'Yo mismo', 'TERCERO_NATURAL'=>'Tercero persona natural', 'PERSONA_JURIDICA'=>'Persona jurídica', 'ENTE_JURIDICO'=>'Ente jurídico',
        'SI_SOY'=>'Sí soy', 'SI_HE_SIDO'=>'Sí he sido', 'NO_SOY'=>'No soy', 'NO_HE_SIDO'=>'No he sido',
        'SI_ES'=>'Sí es', 'SI_HA_SIDO'=>'Sí ha sido', 'NO_ES'=>'No es', 'NO_HA_SIDO'=>'No ha sido',
    ];

    public static function etiqueta(?string $valor): string
    {
        if (!$valor) { return ''; }
        return TipoDocumentoIdentidad::tryFrom($valor)?->label()
            ?? EstadoCivil::tryFrom($valor)?->label()
            ?? self::ETIQUETAS[$valor]
            ?? ucfirst(mb_strtolower(str_replace('_', ' ', $valor)));
    }

    public static function marca(?string $actual, string $opcion): string { return $actual === $opcion ? 'X' : ' '; }
    public static function moneda(float|int|string|null $valor): string { return 'S/ '.number_format((float) $valor, 2, '.', ','); }
}
