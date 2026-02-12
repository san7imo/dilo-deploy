<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_personal_expenses', function (Blueprint $table) {
            $table->string('receipt_url')->nullable()->after('description');
            $table->string('receipt_id')->nullable()->after('receipt_url');
        });
    }

    public function down(): void
    {
        Schema::table('event_personal_expenses', function (Blueprint $table) {
            $table->dropColumn(['receipt_url', 'receipt_id']);
        });
    }
};
