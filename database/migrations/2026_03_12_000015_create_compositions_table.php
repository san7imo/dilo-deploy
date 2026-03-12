<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compositions')) {
            return;
        }

        Schema::create('compositions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('iswc', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('title');
            $table->index('iswc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compositions');
    }
};
