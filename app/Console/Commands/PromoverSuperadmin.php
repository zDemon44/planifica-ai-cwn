<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoverSuperadmin extends Command
{
    protected $signature = 'usuario:superadmin {email}';

    protected $description = 'Habilita una cuenta existente como Superadmin sin cambiar su contraseña';

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();
        if (! $usuario) {
            $this->error('No existe una cuenta con ese correo.');

            return self::FAILURE;
        }
        $usuario->role = 'SUPERADMIN';
        $usuario->activo = true;
        $usuario->docente_id = null;
        $usuario->save();
        $this->info('Cuenta habilitada como Superadmin.');

        return self::SUCCESS;
    }
}
