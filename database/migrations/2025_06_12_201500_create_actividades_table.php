<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->bigIncrements('id'); // NOTE: Por si acaso se necesita un ID más grande en el futuro.
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('pertenencia_nombre')->nullable();
            $table->string('pertenencia_tipo')->nullable();
            $table->foreignId('tipo_actividad_id')->constrained('tipos_actividades')->onDelete('restrict');//importante el contraint para evitar problemas con plurales
            $table->string('titulo');
            $table->text('descripcion');
            $table->date('fecha');
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->string('created_by_role')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};