<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_split_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained('tracks')->cascadeOnDelete();
            $table->string('split_type')->default('master');
            $table->string('status', 20)->default('active');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('contract_path');
            $table->string('contract_original_filename');
            $table->string('contract_hash', 64)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['track_id', 'split_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_split_agreements');
    }
};
