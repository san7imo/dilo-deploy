<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('composition_split_participants')) {
            Schema::drop('composition_split_participants');
        }

        Schema::create('composition_split_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('composition_split_agreement_id');
            $table->unsignedBigInteger('artist_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('payee_email')->nullable();
            $table->string('name')->nullable();
            $table->string('role')->default('writer');
            $table->decimal('percentage', 8, 4);
            $table->timestamps();
            $table->softDeletes();

            $table->index('composition_split_agreement_id', 'csp_csa_idx');
            $table->index('artist_id', 'csp_artist_idx');
            $table->index('user_id', 'csp_user_idx');
            $table->index('payee_email', 'csp_payee_email_idx');

            $table->foreign('composition_split_agreement_id', 'csp_csa_fk')
                ->references('id')
                ->on('composition_split_agreements')
                ->cascadeOnDelete();
            $table->foreign('artist_id', 'csp_artist_fk')
                ->references('id')
                ->on('artists')
                ->nullOnDelete();
            $table->foreign('user_id', 'csp_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_split_participants');
    }
};
