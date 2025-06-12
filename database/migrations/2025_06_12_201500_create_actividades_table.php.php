<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('gerencia_id')->constrained()->onDelete('restrict');
            $table->foreignId('tipo_actividad_id')->constrained('tipos_actividad')->onDelete('restrict');
            $table->string('titulo');
            $table->text('descripcion');
            $table->date('fecha');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};