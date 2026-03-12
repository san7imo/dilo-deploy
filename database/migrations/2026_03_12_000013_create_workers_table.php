<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workers')) {
            return;
        }

        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 180);
            $table->string('document_type', 50)->nullable();
            $table->string('document_number', 80)->nullable();
            $table->string('position', 120)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('account_number', 120)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('full_name');
            $table->index('document_number');
            $table->index('position');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
