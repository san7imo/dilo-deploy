<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('event_date');
        });

        DB::table('events')
            ->whereIn('id', function ($query) {
                $query->select('event_id')
                    ->from('event_payments')
                    ->groupBy('event_id')
                    ->havingRaw('SUM(amount_base) > 0');
            })
            ->update(['is_paid' => true]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });
    }
};
