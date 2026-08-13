<?php

namespace App\Services\Geografia;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CatalogoGeograficoService
{
    private const CACHE_PAISES = 'catalogo-geografico:paises:v1';

    private const RESPALDO_PAISES = 'catalogo-geografico:paises:ultimo-valido:v1';

    private const CACHE_UBIGEOS = 'catalogo-geografico:ubigeos:v1';

    private const RESPALDO_UBIGEOS = 'catalogo-geografico:ubigeos:ultimo-valido:v1';

    /** @return array<string, string> */
    public function paises(): array
    {
        $vigente = Cache::get(self::CACHE_PAISES);

        if (is_array($vigente) && $vigente !== []) {
            return $vigente;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(4)
                ->timeout(12)
                ->get((string) config('services.geografia.paises_url'));

            if (! $response->successful()) {
                return $this->respaldo(self::RESPALDO_PAISES, $response->status());
            }

            $catalogo = $response->json();

            $nombres = collect(is_array($catalogo) ? array_values($catalogo) : [])
                ->filter(fn ($nombre): bool => is_string($nombre) && trim($nombre) !== '')
                ->map(fn (string $nombre): string => trim($nombre))
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->mapWithKeys(fn (string $nombre): array => [$nombre => $nombre])
                ->all();

            if ($nombres === []) {
                return $this->respaldo(self::RESPALDO_PAISES);
            }

            Cache::put(self::CACHE_PAISES, $nombres, now()->addDays(30));
            Cache::forever(self::RESPALDO_PAISES, $nombres);

            return $nombres;
        } catch (Throwable $exception) {
            Log::warning('No fue posible actualizar el catálogo de países.', [
                'excepcion' => $exception::class,
            ]);

            return Cache::get(self::RESPALDO_PAISES, []);
        }
    }

    /** @return array<string, string> */
    public function departamentos(): array
    {
        return $this->opciones(array_keys($this->catalogoUbigeos()));
    }

    /** @return array<string, string> */
    public function provincias(?string $departamento): array
    {
        $catalogo = $this->catalogoUbigeos();
        $claveDepartamento = $this->claveCoincidente($catalogo, $departamento);

        return $claveDepartamento === null
            ? []
            : $this->opciones(array_keys($catalogo[$claveDepartamento]));
    }

    /** @return array<string, string> */
    public function distritos(?string $departamento, ?string $provincia): array
    {
        $catalogo = $this->catalogoUbigeos();
        $claveDepartamento = $this->claveCoincidente($catalogo, $departamento);

        if ($claveDepartamento === null) {
            return [];
        }

        $provincias = $catalogo[$claveDepartamento];
        $claveProvincia = $this->claveCoincidente($provincias, $provincia);

        return $claveProvincia === null
            ? []
            : $this->opciones(array_keys($provincias[$claveProvincia]));
    }

    /** @return array<string, mixed> */
    private function catalogoUbigeos(): array
    {
        $vigente = Cache::get(self::CACHE_UBIGEOS);

        if (is_array($vigente) && $vigente !== []) {
            return $vigente;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(4)
                ->timeout(12)
                ->get((string) config('services.geografia.ubigeos_url'));

            if (! $response->successful() || ! is_array($response->json()) || $response->json() === []) {
                return $this->respaldo(self::RESPALDO_UBIGEOS, $response->status());
            }

            $catalogo = $response->json();
            Cache::put(self::CACHE_UBIGEOS, $catalogo, now()->addDays(30));
            Cache::forever(self::RESPALDO_UBIGEOS, $catalogo);

            return $catalogo;
        } catch (Throwable $exception) {
            Log::warning('No fue posible actualizar el catálogo de ubigeos.', [
                'excepcion' => $exception::class,
            ]);

            return Cache::get(self::RESPALDO_UBIGEOS, []);
        }
    }

    /** @param array<string, mixed> $catalogo */
    private function claveCoincidente(array $catalogo, ?string $valor): ?string
    {
        $buscado = mb_strtoupper(trim((string) $valor));

        foreach (array_keys($catalogo) as $clave) {
            if (mb_strtoupper(trim((string) $clave)) === $buscado) {
                return (string) $clave;
            }
        }

        return null;
    }

    /** @param list<string> $nombres
     * @return array<string, string>
     */
    private function opciones(array $nombres): array
    {
        return collect($nombres)
            ->map(fn (string $nombre): string => Str::title(Str::lower(trim($nombre))))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (string $nombre): array => [$nombre => $nombre])
            ->all();
    }

    /** @return array<string, string> */
    private function respaldo(string $clave, ?int $estadoHttp = null): array
    {
        Log::warning('El proveedor geográfico no devolvió un catálogo válido.', [
            'estado_http' => $estadoHttp,
        ]);

        return Cache::get($clave, []);
    }
}
