<?php
namespace App\Policies;
use App\Enums\Declaraciones\EstadoDeclaracion;
use App\Models\DeclaracionTrabajador;
use App\Models\User;
class DeclaracionTrabajadorPolicy
{
    private function puede(User $u, string $permiso): bool { return $u->hasRole('super_admin') || $u->can($permiso); }
    public function viewAny(User $u): bool { return $this->puede($u, 'ver_declaraciones_trabajador'); }
    public function view(User $u, DeclaracionTrabajador $d): bool { return $this->puede($u, 'ver_declaraciones_trabajador'); }
    public function create(User $u): bool { return $this->puede($u, 'crear_declaraciones_trabajador'); }
    public function update(User $u, DeclaracionTrabajador $d): bool { return $this->puede($u, 'editar_declaraciones_trabajador') && $d->Estado === EstadoDeclaracion::BORRADOR->value; }
    public function delete(User $u, DeclaracionTrabajador $d): bool { return false; }
    public function finalizar(User $u, DeclaracionTrabajador $d): bool { return $this->puede($u, 'finalizar_declaraciones_trabajador') && $d->Estado === EstadoDeclaracion::BORRADOR->value; }
    public function generarPdf(User $u, DeclaracionTrabajador $d): bool { return $this->puede($u, 'generar_pdf_declaraciones') && $d->Estado === EstadoDeclaracion::FINALIZADO->value; }
    public function descargar(User $u, DeclaracionTrabajador $d): bool { return $this->puede($u, 'descargar_declaraciones'); }
    public function nuevaVersion(User $u, DeclaracionTrabajador $d): bool { return $this->puede($u, 'versionar_declaraciones_trabajador') && $d->Estado === EstadoDeclaracion::FINALIZADO->value; }
}
