<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ya creada la tabla 'gerencias' se le puede agregar la columna 'gerencia_id' a la tabla 'users'.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('gerencia_id')
                ->nullable()
                ->constrained('gerencias')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['gerencia_id']);
            $table->dropColumn('gerencia_id');
        });
    }

};
