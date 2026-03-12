<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recording_compositions')) {
            Schema::create('recording_compositions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('track_id')
                    ->constrained('tracks')
                    ->cascadeOnDelete();
                $table->foreignId('composition_id')
                    ->constrained('compositions')
                    ->cascadeOnDelete();
                $table->string('relationship_type', 32)->default('main_work');
                $table->string('source', 64)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['track_id', 'composition_id'], 'recording_compositions_track_composition_unique');
                $table->index(['composition_id', 'track_id'], 'recording_compositions_composition_track_idx');
            });
        }

        if (Schema::hasTable('composition_track') && Schema::hasTable('recording_compositions')) {
            DB::statement("
                INSERT INTO recording_compositions (track_id, composition_id, relationship_type, source, notes, created_at, updated_at)
                SELECT
                    ct.track_id,
                    ct.composition_id,
                    'main_work',
                    'legacy_composition_track',
                    NULL,
                    COALESCE(ct.created_at, NOW()),
                    COALESCE(ct.updated_at, NOW())
                FROM composition_track ct
                LEFT JOIN recording_compositions rc
                    ON rc.track_id = ct.track_id
                    AND rc.composition_id = ct.composition_id
                WHERE rc.id IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recording_compositions');
    }
};

