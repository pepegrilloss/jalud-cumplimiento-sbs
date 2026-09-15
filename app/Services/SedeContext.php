<?php

namespace App\Services;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SedeContext
{
    public const SESSION_KEY = 'sede_activa';

    /** @return Collection<int, Sede> */
    public function sedesPermitidas(User $user): Collection
    {
        $query = Sede::query()->operativas()->orderBy('Nombre');

        if (! $user->isPrivileged()) {
            $query->where('SedeID', $user->SedeID ?: 0);
        }

        return $query->get();
    }

    public function sedeActivaId(?User $user = null): ?int
    {
        $user ??= auth()->user();
        if (! $user) {
            return null;
        }

        $sedeId = (int) session(self::SESSION_KEY, 0);
        if (! $sedeId || ! $this->usuarioPuedeUsar($user, $sedeId)) {
            session()->forget(self::SESSION_KEY);

            return null;
        }

        return $sedeId;
    }

    public function sedeActiva(?User $user = null): ?Sede
    {
        $sedeId = $this->sedeActivaId($user);

        return $sedeId ? Sede::query()->operativas()->find($sedeId) : null;
    }

    public function seleccionar(User $user, int $sedeId): void
    {
        if (! $this->usuarioPuedeUsar($user, $sedeId)) {
            throw ValidationException::withMessages([
                'sede' => 'La sede seleccionada no está disponible para este usuario.',
            ]);
        }

        session([self::SESSION_KEY => $sedeId]);
    }

    public function usuarioPuedeUsar(User $user, int $sedeId): bool
    {
        return $this->sedesPermitidas($user)->contains(
            fn (Sede $sede): bool => (int) $sede->SedeID === $sedeId,
        );
    }
}
