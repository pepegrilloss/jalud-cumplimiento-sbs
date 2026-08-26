<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $table = 'MP_LGN_USUARIO';
    protected $primaryKey = 'ID_MP_LGN_USUARIO';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'ID_MP_LGN_USUARIO',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ID_MP_LGN_USUARIO' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->hasValidProfile()) {
            return false;
        }

        // El panel es exclusivamente administrativo: TI y Comercial.
        if (!in_array((int) $this->ID_MP_LGN_AREA, [1, 12], true)) {
            return false;
        }

        $activeStates = config('security.access.active_profile_states', []);

        if ($activeStates === []) {
            return true;
        }

        return in_array((string) $this->ID_MP_LGN_ESTADO, $activeStates, true);
    }

    private function hasValidProfile(): bool
    {
        return filled($this->ID_MP_LGN_USUARIOS_REGISTRADOS ?? null)
            && filled($this->ID_MP_LGN_AREA ?? null)
            && filled($this->ID_MP_LGN_ROL ?? null)
            && filled($this->ID_MP_LGN_ESTADO ?? null);
    }

    public function getAuthIdentifierName()
    {
        return 'ID_MP_LGN_USUARIO';
    }

    public function getAuthIdentifier()
    {
        return $this->getAttribute('ID_MP_LGN_USUARIO');
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getAttribute($key)
    {
        if ($key === 'id') {
            return parent::getAttribute('ID_MP_LGN_USUARIO');
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'id') {
            $key = 'ID_MP_LGN_USUARIO';
        }

        return parent::setAttribute($key, $value);
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }
}
