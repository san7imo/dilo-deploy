<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('organizers')) {
            return;
        }

        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 180);
            $table->string('contact_name', 180)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('instagram_url', 255)->nullable();
            $table->string('facebook_url', 255)->nullable();
            $table->string('tiktok_url', 255)->nullable();
            $table->string('x_url', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_name');
            $table->index('contact_name');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};
