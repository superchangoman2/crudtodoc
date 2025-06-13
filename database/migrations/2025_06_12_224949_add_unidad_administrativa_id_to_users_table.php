<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unidades_administrativas_id')
                ->nullable()
                ->constrained('unidades_administrativas')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unidades_administrativas_id']);
            $table->dropColumn('unidades_administrativas_id');
        });
    }

};
