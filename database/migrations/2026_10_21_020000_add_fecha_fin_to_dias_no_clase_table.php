<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dias_no_clase', function (Blueprint $table): void {
            $table->date('fecha_fin')->nullable()->after('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('dias_no_clase', function (Blueprint $table): void {
            $table->dropColumn('fecha_fin');
        });
    }
};
