<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            // ⚙️ Agregar solo la columna faltante
            if (!Schema::hasColumn('releases', 'cover_id')) {
                $table->string('cover_id')->nullable()->after('cover_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropColumn('cover_id');
        });
    }
};
