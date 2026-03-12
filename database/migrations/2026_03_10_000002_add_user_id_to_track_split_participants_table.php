<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('track_split_participants', function (Blueprint $table) {
            if (!Schema::hasColumn('track_split_participants', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('artist_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('track_split_participants', function (Blueprint $table) {
            if (Schema::hasColumn('track_split_participants', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
