<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gerencia_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('gerencia_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('es_gerente')->default(false); // indica si es gerente en esa gerencia
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('gerencia_user');
    }
};
