<?php

namespace Database\Seeders;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfesorPruebaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $docente = Docente::where('identificacion', 'DEMO-DOC-1')->lockForUpdate()->firstOrFail();
            if (User::where('email', 'profesor@pruebas.com')->exists() || $docente->usuario()->exists()) {
                $this->command?->info('La cuenta o el vínculo ya existen; no se modificaron.');

                return;
            }

            $usuario = new User;
            $usuario->name = $docente->nombres.' '.$docente->apellidos;
            $usuario->email = 'profesor@pruebas.com';
            $usuario->password = 'Profesor123!';
            $usuario->role = 'DOCENTE';
            $usuario->activo = true;
            $usuario->docente_id = $docente->id;
            $usuario->save();
        });
    }
}
