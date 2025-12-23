<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Clasificación del evento
            $table->string('event_type')->nullable()->after('event_date');
            $table->string('status')->nullable()->after('is_paid');

            // Localización
            $table->string('country')->nullable()->after('location');
            $table->string('city')->nullable()->after('country');
            $table->string('venue_address')->nullable()->after('city');

            // Fee del show
            $table->decimal('show_fee_total', 15, 2)->nullable()->after('status');
            $table->string('currency', 3)->default('EUR')->after('show_fee_total');
            $table->decimal('advance_percentage', 5, 2)->default(50)->after('currency');
            $table->boolean('advance_expected')->default(true)->after('advance_percentage');
            $table->date('full_payment_due_date')->nullable()->after('advance_expected');

            // Índices
            $table->index('event_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['event_type']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'event_type',
                'status',
                'country',
                'city',
                'venue_address',
                'show_fee_total',
                'currency',
                'advance_percentage',
                'advance_expected',
                'full_payment_due_date'
            ]);
        });
    }
};
