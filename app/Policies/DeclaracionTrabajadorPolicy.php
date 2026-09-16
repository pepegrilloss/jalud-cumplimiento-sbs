<?php

namespace App\Policies;

use App\Models\DeclaracionTrabajador;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeclaracionTrabajadorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_declaracion::trabajador');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $user->can('view_declaracion::trabajador');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_declaracion::trabajador');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $declaracionTrabajador->Estado !== 'FINALIZADO'
            && ($user->hasRole('super_admin') || $user->can('update_declaracion::trabajador'));
    }

    public function finalizar(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $user->hasRole('super_admin') || $user->can('finalizar_declaraciones_trabajador');
    }

    public function generarPdf(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $user->hasRole('super_admin') || $user->can('generar_pdf_declaraciones');
    }

    public function nuevaVersion(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $user->hasRole('super_admin') || $user->can('versionar_declaraciones_trabajador');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $user->can('delete_declaracion::trabajador');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('{{ DeleteAny }}');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $user->can('{{ ForceDelete }}');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('{{ ForceDeleteAny }}');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $user->can('{{ Restore }}');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('{{ RestoreAny }}');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, DeclaracionTrabajador $declaracionTrabajador): bool
    {
        return $user->can('{{ Replicate }}');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('{{ Reorder }}');
    }
}
