<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('artist_share_percentage', 5, 2)->default(70)->after('advance_percentage');
            $table->decimal('label_share_percentage', 5, 2)->default(30)->after('artist_share_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['artist_share_percentage', 'label_share_percentage']);
        });
    }
};
