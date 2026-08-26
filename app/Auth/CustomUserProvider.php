<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        $result = DB::select('SELECT * FROM MP_LGN_USUARIO WHERE ID_MP_LGN_USUARIO = ?', [$identifier]);

        if (!empty($result)) {
            return self::buildUser($result[0]);
        }

        return null;
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return null;
        }

        $result = DB::select('EXEC PA_LOGIN_MLP @usuario = ?', [trim($credentials['email'])]);

        if (!empty($result)) {
            return self::buildUser($result[0]);
        }

        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return Hash::check($credentials['password'], $user->password);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
    }

    private static function buildUser(object $userData): User
    {
        $user = new User();
        $user->id = $userData->ID_MP_LGN_USUARIO;
        $user->name = $userData->USUARIO;
        $user->email = trim($userData->USUARIO);
        $passwordColumn = 'CONTRASEÑA';
        $legacyPasswordColumn = 'CONTRASEÃ‘A';
        $user->password = trim($userData->{$passwordColumn} ?? $userData->{$legacyPasswordColumn} ?? '');

        $perfil = DB::table('MP_LGN_USUARIOS_REGISTRADOS')
            ->where('ID_MP_LGN_USUARIO', $userData->ID_MP_LGN_USUARIO)
            ->orderBy('ID_MP_LGN_USUARIOS_REGISTRADOS', 'desc')
            ->first();

        if ($perfil) {
            $user->ID_MP_LGN_USUARIOS_REGISTRADOS = $perfil->ID_MP_LGN_USUARIOS_REGISTRADOS;
            $user->ID_MP_LGN_AREA = $perfil->ID_MP_LGN_AREA;
            $user->ID_MP_LGN_ROL = $perfil->ID_MP_LGN_ROL;
            $user->ID_MP_LGN_ESTADO = $perfil->ID_MP_LGN_ESTADO;
            $user->ID_MLP_PLANTA_EMPAQUE = $perfil->ID_MLP_PLANTA_EMPAQUE;
        }

        return $user;
    }
}
