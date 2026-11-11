<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('curriculos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->string('nombre');
            $table->boolean('vigente')->default(false);
            $table->timestamps();
        });

        $ahora = now();
        DB::table('curriculos')->insert([
            [
                'id' => 1,
                'codigo' => 'priorizado-2025',
                'nombre' => 'Currículo priorizado 2025',
                'vigente' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'id' => 2,
                'codigo' => 'obligatorio-elemental',
                'nombre' => 'Currículo de los Niveles de Educación Obligatoria - Subnivel Elemental',
                'vigente' => false,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculos');
    }
};
