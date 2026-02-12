<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'whatsapp_event')) {
                $table->string('whatsapp_event', 50)->nullable();
            }
            if (!Schema::hasColumn('events', 'page_tickets')) {
                $table->string('page_tickets', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'page_tickets')) {
                $table->dropColumn('page_tickets');
            }
            if (Schema::hasColumn('events', 'whatsapp_event')) {
                $table->dropColumn('whatsapp_event');
            }
        });
    }
};
