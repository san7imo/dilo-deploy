<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_artist_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->foreignId('track_id')
                ->nullable()
                ->constrained('tracks')
                ->nullOnDelete();
            $table->foreignId('track_split_participant_id')
                ->nullable()
                ->constrained('track_split_participants')
                ->nullOnDelete();
            $table->foreignId('invited_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('accepted_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('invitee_name')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['email', 'accepted_at'], 'external_artist_invitations_email_accepted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_artist_invitations');
    }
};
