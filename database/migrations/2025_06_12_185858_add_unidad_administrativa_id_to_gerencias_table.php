<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gerencias', function (Blueprint $table) {
            // Asegúrate que la tabla unidades_administrativas exista antes de esta migración
            $table->foreignId('unidad_administrativa_id')
                ->nullable()
                ->constrained('unidades_administrativas')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('gerencias', function (Blueprint $table) {
            $table->dropForeign(['unidad_administrativa_id']);
            $table->dropColumn('unidad_administrativa_id');
        });
    }
};