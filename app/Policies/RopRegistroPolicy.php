<?php

namespace App\Policies;

use App\Models\RopRegistro;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RopRegistroPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rop::registro');
    }

    public function view(User $user, RopRegistro $registro): bool
    {
        return $user->can('view_rop::registro');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RopRegistro $registro): bool
    {
        return $user->can('completar_rop');
    }

    public function delete(User $user, RopRegistro $registro): bool
    {
        return false;
    }
}
