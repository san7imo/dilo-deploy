<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_registrations')) {
            return;
        }

        Schema::create('composition_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composition_id')
                ->constrained('compositions')
                ->cascadeOnDelete();
            $table->string('registration_type', 32)->default('iswc');
            $table->string('society_name', 128)->nullable();
            $table->string('registration_number', 128);
            $table->string('territory_code', 8)->nullable();
            $table->string('status', 24)->default('active');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['composition_id', 'registration_type']);
            $table->index(['registration_number']);
            $table->index(['status']);
            $table->unique(
                ['composition_id', 'registration_type', 'registration_number'],
                'composition_registrations_comp_type_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_registrations');
    }
};
