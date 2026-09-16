<?php

namespace App\Policies;

use App\Models\DeclaracionCliente;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeclaracionClientePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_declaracion::cliente');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DeclaracionCliente $declaracionCliente): bool
    {
        return $user->can('view_declaracion::cliente');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_declaracion::cliente');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DeclaracionCliente $declaracionCliente): bool
    {
        return $declaracionCliente->Estado !== 'FINALIZADO'
            && ($user->hasRole('super_admin') || $user->can('update_declaracion::cliente'));
    }

    public function finalizar(User $user, DeclaracionCliente $declaracionCliente): bool
    {
        return $user->hasRole('super_admin') || $user->can('finalizar_declaraciones_cliente');
    }

    public function generarPdf(User $user, DeclaracionCliente $declaracionCliente): bool
    {
        return $user->hasRole('super_admin') || $user->can('generar_pdf_declaraciones');
    }

    public function descargar(User $user, DeclaracionCliente $declaracionCliente): bool
    {
        return $user->hasRole('super_admin') || $user->can('view_declaracion::cliente');
    }

    public function nuevaVersion(User $user, DeclaracionCliente $declaracionCliente): bool
    {
        return $user->hasRole('super_admin') || $user->can('versionar_declaraciones_cliente');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DeclaracionCliente $declaracionCliente): bool
    {
        return $user->can('delete_declaracion::cliente');
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
    public function forceDelete(User $user, DeclaracionCliente $declaracionCliente): bool
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
    public function restore(User $user, DeclaracionCliente $declaracionCliente): bool
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
    public function replicate(User $user, DeclaracionCliente $declaracionCliente): bool
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
