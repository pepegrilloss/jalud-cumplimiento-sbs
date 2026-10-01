<?php

namespace App\Observers;

use App\Models\RopAdjunto;
use App\Models\RopRegistro;
use App\Services\Rop\RopAuditService;
use Illuminate\Support\Facades\Storage;

class RopAdjuntoObserver
{
    public function created(RopAdjunto $adjunto): void
    {
        $this->auditar($adjunto, 'ADJUNTAR_EVIDENCIA', [], $this->datos($adjunto));
    }

    public function updated(RopAdjunto $adjunto): void
    {
        $cambios = $adjunto->getChanges();
        $anteriores = collect(array_keys($cambios))->mapWithKeys(fn (string $campo) => [$campo => $adjunto->getOriginal($campo)])->all();

        if (isset($cambios['RutaArchivo']) && filled($adjunto->getOriginal('RutaArchivo'))) {
            Storage::disk('local')->delete($adjunto->getOriginal('RutaArchivo'));
        }

        $this->auditar($adjunto, 'ACTUALIZAR_EVIDENCIA', $anteriores, $this->datos($adjunto));
    }

    public function deleted(RopAdjunto $adjunto): void
    {
        $this->auditar($adjunto, 'ELIMINAR_EVIDENCIA', $this->datos($adjunto), []);

        if (filled($adjunto->RutaArchivo)) {
            Storage::disk('local')->delete($adjunto->RutaArchivo);
        }
    }

    private function auditar(RopAdjunto $adjunto, string $accion, array $anteriores, array $nuevos): void
    {
        $registro = RopRegistro::withoutGlobalScopes()->find($adjunto->RopRegistroID);

        if (! $registro) {
            return;
        }

        app(RopAuditService::class)->registrar(
            $accion,
            $registro,
            $anteriores,
            $nuevos + ['RopAdjuntoID' => $adjunto->getKey()],
            $adjunto->Descripcion,
        );
    }

    private function datos(RopAdjunto $adjunto): array
    {
        return collect($adjunto->only(['NombreOriginal', 'TipoMime', 'TamanioBytes', 'Descripcion', 'Categoria', 'HashSha256']))
            ->all();
    }
}
