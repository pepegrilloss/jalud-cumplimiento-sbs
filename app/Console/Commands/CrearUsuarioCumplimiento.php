<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CrearUsuarioCumplimiento extends Command
{
    protected $signature = 'cumplimiento:crear-usuario
        {username : Usuario de acceso}
        {nombre : Nombre completo}
        {--password= : Contraseña inicial; se solicita si se omite}
        {--email= : Correo opcional}
        {--sede= : ID de sede de Jalud}
        {--rol=oficial_cumplimiento_sbs : Rol propio de Cumplimiento}';

    protected $description = 'Crea un usuario independiente para el portal de Cumplimiento SBS.';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Contraseña inicial (mínimo 10 caracteres)');
        if (mb_strlen((string) $password) < 10) {
            $this->error('La contraseña debe tener como mínimo 10 caracteres.');
            return self::FAILURE;
        }

        $role = Role::findByName($this->option('rol'), 'web');
        $user = User::query()->updateOrCreate(
            ['username' => $this->argument('username')],
            [
                'name' => $this->argument('nombre'),
                'email' => $this->option('email'),
                'SedeID' => $this->option('sede'),
                'password' => Hash::make($password),
            ],
        );
        $user->syncRoles([$role]);
        $this->info("Usuario {$user->username} listo con el rol {$role->name}.");

        return self::SUCCESS;
    }
}
