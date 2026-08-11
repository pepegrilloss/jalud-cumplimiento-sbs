<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable, HasRoles;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password', 'username', 'SedeID'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'cumplimiento' && $this->puedeAccederACumplimientoSbs();
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'SedeID', 'SedeID');
    }

    public function puedeAccederACumplimientoSbs(): bool
    {
        return $this->hasPermissionTo('acceder_cumplimiento_sbs')
            || $this->hasRole('super_admin');
    }

    public function getEffectiveSedeId(): ?int
    {
        return $this->SedeID ? (int) $this->SedeID : null;
    }

    public function isPrivileged(): bool
    {
        return $this->hasAnyPermission(['ver_todas_las_sedes', 'ver_todos_los_casos_sbs'])
            || $this->hasRole('super_admin');
    }
}
