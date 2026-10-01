<?php

namespace App\Services\Rop;

use App\Models\Credito;
use App\Models\RopPeriodo;
use App\Models\RopRegistro;
use App\Models\RosConfiguracion;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RopDetectionService
{
    public function __construct(
        private readonly RopDetectionRule $rule,
        private readonly RopSnapshotService $snapshots,
        private readonly RopAuditService $audit,
    ) {}

    public function detectar(
        ?CarbonInterface $desde = null,
        ?CarbonInterface $hasta = null,
        ?int $sedeId = null,
    ): RopDetectionResult {
        $desde = $desde
            ? CarbonImmutable::instance($desde)->startOfDay()
            : CarbonImmutable::instance(now())->startOfMonth();
        $hasta = $hasta
            ? CarbonImmutable::instance($hasta)->endOfDay()
            : CarbonImmutable::instance(now())->endOfMonth();
        $creados = $duplicados = $cerrados = 0;

        $configuraciones = RosConfiguracion::withoutGlobalScopes()
            ->when($sedeId, fn (Builder $query) => $query->where('SedeID', $sedeId))
            ->whereNotNull('UmbralRop')
            ->get();

        if ($sedeId && $configuraciones->isEmpty()) {
            return new RopDetectionResult(sedesSinConfiguracion: 1);
        }

        foreach ($configuraciones as $configuracion) {
            Credito::withoutGlobalScopes()
                ->where('SedeID', $configuracion->SedeID)
                ->whereBetween('FechaGeneracion', [$desde->startOfDay(), $hasta->endOfDay()])
                ->whereHas('proposicion', fn (Builder $query) => $query
                    ->withoutGlobalScopes()
                    ->where('Estado', 'APROBADO')
                    ->where('Eliminado', false)
                    ->where('MontoTotal', '>=', $configuracion->UmbralRop))
                ->with([
                    'proposicion' => fn ($query) => $query->withoutGlobalScopes(),
                    'proposicion.cliente' => fn ($query) => $query->withoutGlobalScopes(),
                ])
                ->orderBy('CreditoID')
                ->chunkById(200, function ($creditos) use ($configuracion, &$creados, &$duplicados, &$cerrados): void {
                    foreach ($creditos as $credito) {
                        $monto = (float) $credito->proposicion->MontoTotal;
                        $origenTipo = RopRegistro::ORIGEN_CREDITO_DESEMBOLSO;
                        $origenId = (int) $credito->CreditoID;

                        if (! $this->rule->qualifies($monto, (float) $configuracion->UmbralRop)) {
                            continue;
                        }

                        if (RopRegistro::withoutGlobalScopes()
                            ->where('OrigenTipo', $origenTipo)
                            ->where('OrigenID', $origenId)
                            ->exists()) {
                            $duplicados++;

                            continue;
                        }

                        $fecha = $credito->FechaGeneracion
                            ?? $credito->proposicion->FechaDesembolso
                            ?? $credito->proposicion->FechaPropuesta;
                        $mes = CarbonImmutable::parse($fecha)->startOfMonth();
                        DB::transaction(function () use ($credito, $configuracion, $fecha, $mes, $monto, $origenTipo, $origenId, &$creados, &$duplicados, &$cerrados): void {
                            $periodo = RopPeriodo::withoutGlobalScopes()->firstOrCreate(
                                ['SedeID' => $configuracion->SedeID, 'Periodo' => $mes->toDateString()],
                                ['Estado' => RopPeriodo::ESTADO_ABIERTO],
                            );
                            $periodo = RopPeriodo::withoutGlobalScopes()
                                ->whereKey($periodo->getKey())
                                ->lockForUpdate()
                                ->firstOrFail();

                            if ($periodo->Estado === RopPeriodo::ESTADO_CERRADO) {
                                $cerrados++;

                                return;
                            }

                            $snapshot = $this->snapshots->crear($credito, $configuracion);
                            $registro = RopRegistro::withoutGlobalScopes()->firstOrCreate(
                                ['OrigenTipo' => $origenTipo, 'OrigenID' => $origenId],
                                [
                                    'RopPeriodoID' => $periodo->RopPeriodoID,
                                    'SedeID' => $configuracion->SedeID,
                                    'ZonaID' => $credito->proposicion->ZonaID,
                                    'ClienteID' => $credito->proposicion->ClienteID,
                                    'ProposicionCreditoID' => $credito->ProposicionCreditoID,
                                    'CreditoID' => $credito->CreditoID,
                                    'CodigoCredito' => $credito->proposicion->CodigoCredito,
                                    'ClienteNombre' => $credito->proposicion->cliente->NombresApellidos,
                                    'NumeroDocumento' => $credito->proposicion->cliente->DNI,
                                    'Periodo' => $mes->toDateString(),
                                    'FechaOperacion' => $fecha,
                                    'Monto' => $monto,
                                    'UmbralAplicado' => $configuracion->UmbralRop,
                                    'Moneda' => 'PEN',
                                    'Estado' => RopRegistro::ESTADO_PENDIENTE,
                                    'SnapshotDatos' => $snapshot,
                                    'CreadoPorID' => auth()->id(),
                                ],
                            );

                            if (! $registro->wasRecentlyCreated) {
                                $duplicados++;

                                return;
                            }

                            $registro->NumeroRop = 'ROP-'.str_pad((string) $registro->getKey(), 6, '0', STR_PAD_LEFT);
                            $registro->saveQuietly();
                            $this->audit->registrar('CREAR', $registro, nuevos: $registro->attributesToArray());
                            $creados++;
                        });
                    }
                }, 'CreditoID');
        }

        return new RopDetectionResult($creados, $duplicados, $cerrados);
    }
}
