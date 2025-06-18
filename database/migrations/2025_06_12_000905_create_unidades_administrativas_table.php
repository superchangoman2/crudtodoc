<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unidades_administrativas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
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
        Schema::dropIfExists('unidades_administrativas');
    }
};
