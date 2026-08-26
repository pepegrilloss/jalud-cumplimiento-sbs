<?php

namespace App\Policies;

use App\Models\TrabajadorArchivo;
use App\Models\User;

class TrabajadorArchivoPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver_declaraciones_trabajador');
    }

    public function view(User $user, TrabajadorArchivo $archivo): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'editar_declaraciones_trabajador');
    }

    public function delete(User $user, TrabajadorArchivo $archivo): bool
    {
        return $this->create($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->create($user);
    }

    private function puede(User $user, string $permiso): bool
    {
        return $user->hasRole('super_admin') || $user->can($permiso);
    }
}
