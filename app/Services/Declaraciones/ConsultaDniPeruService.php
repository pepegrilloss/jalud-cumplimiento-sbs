<?php

namespace App\Services\Declaraciones;

use App\Exceptions\ConsultaDniPeruException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConsultaDniPeruService
{
    public function consultar(string $dni): ?array
    {
        $dni = trim($dni);

        if (preg_match('/^\d{8}$/', $dni) !== 1) {
            return null;
        }

        $cacheKey = "apis-net-pe:dni:{$dni}";
        if ($persona = Cache::get($cacheKey)) {
            return $persona;
        }

        $token = (string) config('services.apis_net_pe.token');
        if ($token === '') {
            Log::warning('La consulta de DNI no está configurada: falta APIS_NET_PE_TOKEN.');
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Referer' => (string) config('services.apis_net_pe.referer'),
                ])
                ->connectTimeout(4)
                ->timeout(12)
                ->get((string) config('services.apis_net_pe.url'), ['numero' => $dni]);

            if ($response->status() === 401) {
                throw new ConsultaDniPeruException('apis.net.pe rechazó la consulta. Verifique el token de apis.net.pe.');
            }

            if ($response->status() === 429) {
                throw new ConsultaDniPeruException('Se alcanzó la cuota o el límite de consultas de apis.net.pe.');
            }

            if (! $response->successful()) {
                Log::warning('apis.net.pe no devolvió una consulta de DNI válida.', ['estado_http' => $response->status()]);
                return null;
            }

            $persona = [
                'dni' => $dni,
                'nombres' => $this->normalizar($response->json('nombres')),
                'apellidoPaterno' => $this->normalizar($response->json('apellidoPaterno')),
                'apellidoMaterno' => $this->normalizar($response->json('apellidoMaterno')),
            ];

            if ($persona['nombres'] === '' || $persona['apellidoPaterno'] === '') {
                return null;
            }

            Cache::put($cacheKey, $persona, now()->addDays(30));

            return $persona;
        } catch (ConsultaDniPeruException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('apis.net.pe no está disponible para consulta de DNI.', [
                'tipo_error' => class_basename($exception),
            ]);

            return null;
        }
    }

    private function normalizar(?string $valor): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $valor));
    }
}
