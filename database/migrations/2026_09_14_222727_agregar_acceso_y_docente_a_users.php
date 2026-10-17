<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version_acceso')->default(1);
            $table->foreignId('docente_id')->nullable()->unique()->constrained('docentes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['docente_id']);
            $table->dropUnique(['docente_id']);
            $table->dropColumn(['docente_id', 'activo', 'version_acceso']);
        });
    }
};
