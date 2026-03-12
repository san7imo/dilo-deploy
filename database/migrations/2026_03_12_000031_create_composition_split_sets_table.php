<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_split_sets')) {
            return;
        }

        Schema::create('composition_split_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composition_id')
                ->constrained('compositions', 'id', 'css_composition_fk')
                ->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('active');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('contract_path')->nullable();
            $table->string('contract_original_filename')->nullable();
            $table->string('contract_hash')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users', 'id', 'css_created_by_fk')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['composition_id', 'version'], 'css_composition_version_uk');
            $table->index(['composition_id', 'status'], 'css_composition_status_idx');
            $table->index(['effective_from', 'effective_to'], 'css_effective_range_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_split_sets');
    }
};

