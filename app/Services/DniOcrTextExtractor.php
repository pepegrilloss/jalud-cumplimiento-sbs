<?php

namespace App\Services;

class DniOcrTextExtractor
{
    private const PALABRAS_EXCLUIDAS = [
        'REPUBLICA', 'REPÚBLICA', 'PERU', 'PERÚ', 'REGISTRO', 'NACIONAL', 'IDENTIFICACION', 'IDENTIFICACIÓN',
        'ESTADO', 'CIVIL', 'FECHA', 'NACIMIENTO', 'CADUCIDAD', 'EMISION', 'EMISIÓN', 'DOCUMENTO', 'IDENTIDAD',
        'CUI', 'DNI', 'SEXO', 'NACIONALIDAD', 'SOLTERO', 'SOLTERA', 'CASADO', 'CASADA', 'DIVORCIADO', 'DIVORCIADA',
        'VIUDO', 'VIUDA', 'TARJETA', 'FIRMA', 'RENIEC', 'ELECTORAL', 'DOMICILIO', 'DIRECCION', 'DIRECCIÓN', 'UBIGEO',
        'GRUPO', 'VOTACION', 'VOTACIÓN', 'DONACION', 'DONACIÓN', 'ORGANOS', 'ÓRGANOS', 'PRENOMBRES', 'PRENOMBRE',
        'APELLIDOS', 'APELLIDO', 'NOMBRES', 'NOMBRE', 'PER',
    ];

    public function extraer(array $lecturas): array
    {
        $lecturas = array_values(array_filter(array_map(
            fn (mixed $lectura): ?array => $this->normalizarLectura($lectura),
            $lecturas
        )));

        return [
            'dni' => $this->extraerDni($lecturas),
            'nombres' => $this->extraerNombres($lecturas),
        ];
    }

    private function extraerDni(array $lecturas): ?string
    {
        $candidatos = [];

        foreach ($lecturas as $lectura) {
            foreach ($lectura as $linea) {
                $texto = mb_strtoupper($linea['texto'], 'UTF-8');

                preg_match_all(
                    '/(?<![A-Z0-9])([0-9OILSBZ]{8})(?:\s*-\s*([0-9OILSBZ]))?(?![A-Z0-9])/u',
                    $texto,
                    $coincidencias,
                    PREG_SET_ORDER
                );

                foreach ($coincidencias as $coincidencia) {
                    $dni = $this->normalizarDigitos($coincidencia[1]);
                    if (! preg_match('/^\d{8}$/', $dni)) {
                        continue;
                    }

                    $puntaje = $this->pareceFechaCompacta($dni) ? -4.0 : 5.0;
                    $puntaje += max(0.0, min(100.0, $linea['confianza'])) / 18;

                    if (! empty($coincidencia[2])) {
                        $puntaje += 10;
                    }
                    if (preg_match('/\b(?:CUI|DNI|DOCUMENTO)\b/u', $texto)) {
                        $puntaje += 12;
                    }
                    if ($puntaje <= 0) {
                        continue;
                    }

                    $candidatos[$dni] = ($candidatos[$dni] ?? 0.0) + $puntaje;
                }
            }
        }

        if ($candidatos === []) {
            return null;
        }

        arsort($candidatos, SORT_NUMERIC);
        $mejor = array_key_first($candidatos);

        return $mejor !== null ? (string) $mejor : null;
    }

    private function extraerNombres(array $lecturas): ?string
    {
        $candidatos = [];

        foreach ($lecturas as $lectura) {
            $porEtiquetas = $this->extraerNombresPorEtiquetas($lectura);
            if ($porEtiquetas !== null) {
                $this->sumarCandidatoNombre(
                    $candidatos,
                    $porEtiquetas['valor'],
                    35,
                    $porEtiquetas['confianza'],
                    $porEtiquetas['confianza_minima']
                );
            }

            $lineasNombre = [];

            foreach ($lectura as $indice => $linea) {
                if ($linea['confianza'] < 30) {
                    continue;
                }

                $valor = $this->normalizarNombre($linea['texto']);
                if ($valor === null || ! $this->pareceLineaDeNombre($valor)) {
                    continue;
                }

                $lineasNombre[] = [
                    'indice' => $indice,
                    'valor' => $valor,
                    'confianza' => $linea['confianza'],
                    'confianza_minima' => $linea['confianza_minima'],
                ];

                $this->sumarCandidatoNombre(
                    $candidatos,
                    $valor,
                    4,
                    $linea['confianza'],
                    $linea['confianza_minima']
                );
            }

            $cantidad = count($lineasNombre);

            for ($i = 0; $i < $cantidad - 1; $i++) {
                $actual = $lineasNombre[$i];
                $siguiente = $lineasNombre[$i + 1];

                if (($siguiente['indice'] - $actual['indice']) > 3) {
                    continue;
                }

                $completo = trim($actual['valor'].' '.$siguiente['valor']);
                if (! $this->pareceNombreCompleto($completo)) {
                    continue;
                }

                $this->sumarCandidatoNombre(
                    $candidatos,
                    $completo,
                    22,
                    ($actual['confianza'] + $siguiente['confianza']) / 2,
                    min($actual['confianza_minima'], $siguiente['confianza_minima'])
                );
            }
        }

        if ($candidatos === []) {
            return null;
        }

        uasort($candidatos, function (array $a, array $b): int {
            $puntajeA = $a['mejor'] + min(max($a['apariciones'] - 1, 0), 3) * 0.75;
            $puntajeB = $b['mejor'] + min(max($b['apariciones'] - 1, 0), 3) * 0.75;

            if (abs($puntajeA - $puntajeB) > 0.001) {
                return $puntajeB <=> $puntajeA;
            }

            return mb_strlen($b['valor'], 'UTF-8') <=> mb_strlen($a['valor'], 'UTF-8');
        });

        $mejor = reset($candidatos);
        if (! is_array($mejor) || ! $this->pareceNombreCompleto($mejor['valor'])) {
            return null;
        }

        return mb_substr($mejor['valor'], 0, 150, 'UTF-8');
    }

    private function extraerNombresPorEtiquetas(array $lineas): ?array
    {
        $apellidos = null;
        $prenombres = null;

        foreach ($lineas as $indice => $linea) {
            if (preg_match('/\bAP[E3]LL[I1]D[O0]S?\b/iu', $linea['texto'])) {
                $apellidos = $this->valorJuntoAEtiqueta(
                    $lineas,
                    $indice,
                    '/^.*?\bAP[E3]LL[I1]D[O0]S?\b\s*[:.\-]?\s*/iu'
                );
            }

            if (preg_match('/\b(?:PR[E3]N[O0]MBR[E3]S?|N[O0]MBR[E3]S?)\b/iu', $linea['texto'])) {
                $prenombres = $this->valorJuntoAEtiqueta(
                    $lineas,
                    $indice,
                    '/^.*?\b(?:PR[E3]N[O0]MBR[E3]S?|N[O0]MBR[E3]S?)\b\s*[:.\-]?\s*/iu'
                );
            }
        }

        if (! $apellidos || ! $prenombres) {
            return null;
        }

        $completo = trim($apellidos['valor'].' '.$prenombres['valor']);
        if (! $this->pareceNombreCompleto($completo)) {
            return null;
        }

        return [
            'valor' => $completo,
            'confianza' => ($apellidos['confianza'] + $prenombres['confianza']) / 2,
            'confianza_minima' => min($apellidos['confianza_minima'], $prenombres['confianza_minima']),
        ];
    }

    private function valorJuntoAEtiqueta(array $lineas, int $indice, string $patron): ?array
    {
        $mismaLinea = preg_replace($patron, '', $lineas[$indice]['texto']) ?? '';
        $valor = $this->normalizarNombre($mismaLinea);

        if ($valor !== null && $this->pareceLineaDeNombre($valor)) {
            return [
                'valor' => $valor,
                'confianza' => $lineas[$indice]['confianza'],
                'confianza_minima' => $lineas[$indice]['confianza_minima'],
            ];
        }

        for ($salto = 1; $salto <= 2; $salto++) {
            if (! isset($lineas[$indice + $salto])) {
                break;
            }

            $valor = $this->normalizarNombre($lineas[$indice + $salto]['texto']);
            if ($valor !== null && $this->pareceLineaDeNombre($valor)) {
                return [
                    'valor' => $valor,
                    'confianza' => $lineas[$indice + $salto]['confianza'],
                    'confianza_minima' => $lineas[$indice + $salto]['confianza_minima'],
                ];
            }
        }

        return null;
    }

    private function pareceLineaDeNombre(string $valor): bool
    {
        $palabras = preg_split('/\s+/', $valor) ?: [];
        $cantidad = count($palabras);

        if ($cantidad < 1 || $cantidad > 7 || mb_strlen($valor, 'UTF-8') < 4 || mb_strlen($valor, 'UTF-8') > 80) {
            return false;
        }

        $tienePalabraUtil = false;

        foreach ($palabras as $palabra) {
            if (in_array($palabra, self::PALABRAS_EXCLUIDAS, true)) {
                return false;
            }

            if (mb_strlen($palabra, 'UTF-8') >= 3) {
                $tienePalabraUtil = true;
            }
        }

        return $tienePalabraUtil;
    }

    private function pareceNombreCompleto(string $valor): bool
    {
        if (! $this->pareceLineaDeNombre($valor)) {
            return false;
        }

        $palabras = preg_split('/\s+/', $valor) ?: [];

        return count($palabras) >= 2 && count($palabras) <= 7 && mb_strlen($valor, 'UTF-8') >= 8;
    }

    private function sumarCandidatoNombre(
        array &$candidatos,
        string $valor,
        float $base,
        float $confianza,
        float $confianzaMinima
    ): void {
        $valor = trim($valor);
        if ($valor === '') {
            return;
        }

        $palabras = preg_split('/\s+/', $valor) ?: [];
        $penalizacion = 0.0;

        foreach ($palabras as $palabra) {
            if (mb_strlen($palabra, 'UTF-8') <= 2 && ! in_array($palabra, ['DE', 'LA', 'Y'], true)) {
                $penalizacion += 5;
            }
        }

        $puntaje = $base
            + min(count($palabras), 5)
            + max(0.0, min(100.0, $confianza)) * 0.14
            + max(0.0, min(100.0, $confianzaMinima)) * 0.16
            - $penalizacion;

        if (! isset($candidatos[$valor])) {
            $candidatos[$valor] = [
                'valor' => $valor,
                'mejor' => $puntaje,
                'apariciones' => 1,
            ];

            return;
        }

        $candidatos[$valor]['mejor'] = max($candidatos[$valor]['mejor'], $puntaje);
        $candidatos[$valor]['apariciones']++;
    }

    private function normalizarLectura(mixed $lectura): ?array
    {
        if (is_string($lectura)) {
            $lineas = [];

            foreach (preg_split('/\R/', $this->normalizarTexto($lectura)) ?: [] as $texto) {
                $texto = trim($texto);
                if ($texto === '') {
                    continue;
                }

                $lineas[] = [
                    'texto' => $texto,
                    'confianza' => 75.0,
                    'confianza_minima' => 75.0,
                ];
            }

            return $lineas !== [] ? $lineas : null;
        }

        if (! is_array($lectura) || ! isset($lectura['lineas']) || ! is_array($lectura['lineas'])) {
            return null;
        }

        $lineas = [];

        foreach ($lectura['lineas'] as $linea) {
            if (! is_array($linea) || ! isset($linea['texto'])) {
                continue;
            }

            $texto = trim((string) $linea['texto']);
            if ($texto === '') {
                continue;
            }

            $lineas[] = [
                'texto' => $texto,
                'confianza' => (float) ($linea['confianza'] ?? 50),
                'confianza_minima' => (float) ($linea['confianza_minima'] ?? $linea['confianza'] ?? 50),
            ];
        }

        return $lineas !== [] ? $lineas : null;
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace('/[\t ]+/', ' ', $texto) ?? '';
        $texto = preg_replace('/\n{3,}/', "\n\n", $texto) ?? '';

        return trim($texto);
    }

    private function normalizarNombre(string $valor): ?string
    {
        $valor = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]/u', ' ', $valor) ?? '';
        $valor = preg_replace('/\s+/', ' ', trim($valor)) ?? '';

        if ($valor === '') {
            return null;
        }

        $palabras = preg_split('/\s+/', $valor) ?: [];

        while (count($palabras) > 1 && $this->esRuidoCortoDeBorde($palabras[0], false)) {
            array_shift($palabras);
        }

        while (count($palabras) > 1 && $this->esRuidoCortoDeBorde($palabras[array_key_last($palabras)], true)) {
            array_pop($palabras);
        }

        $valor = mb_strtoupper(implode(' ', $palabras), 'UTF-8');

        return preg_match('/^[A-ZÁÉÍÓÚÜÑ\- ]+$/u', $valor) ? $valor : null;
    }

    private function esRuidoCortoDeBorde(string $palabra, bool $final): bool
    {
        $mayuscula = mb_strtoupper($palabra, 'UTF-8');

        if ($final && in_array($mayuscula, ['DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y'], true)) {
            return true;
        }

        if (in_array($mayuscula, ['DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y'], true)) {
            return false;
        }

        return mb_strlen($palabra, 'UTF-8') <= 2 && $palabra !== $mayuscula;
    }

    private function normalizarDigitos(string $valor): string
    {
        return strtr($valor, [
            'O' => '0',
            'I' => '1',
            'L' => '1',
            'S' => '5',
            'B' => '8',
            'Z' => '2',
        ]);
    }

    private function pareceFechaCompacta(string $valor): bool
    {
        if (! preg_match('/^(\d{2})(\d{2})(\d{4})$/', $valor, $partes)) {
            return false;
        }

        $dia = (int) $partes[1];
        $mes = (int) $partes[2];
        $anio = (int) $partes[3];

        return checkdate($mes, $dia, $anio) && $anio >= 1900 && $anio <= ((int) date('Y') + 20);
    }
}
