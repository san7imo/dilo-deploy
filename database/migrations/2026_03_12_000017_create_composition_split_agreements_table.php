<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_split_agreements')) {
            return;
        }

        Schema::create('composition_split_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composition_id')->constrained('compositions')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('contract_path')->nullable();
            $table->string('contract_original_filename')->nullable();
            $table->string('contract_hash')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['composition_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_split_agreements');
    }
};
