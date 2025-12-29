<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_road_manager', function (Blueprint $table) {
            $table->timestamp('payment_confirmed_at')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_road_manager', function (Blueprint $table) {
            $table->dropColumn('payment_confirmed_at');
        });
    }
};
