<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'organizer_company_name')) {
                $table->string('organizer_company_name', 180)->nullable()->after('page_tickets');
            }
            if (!Schema::hasColumn('events', 'organizer_contact_name')) {
                $table->string('organizer_contact_name', 180)->nullable()->after('organizer_company_name');
            }
            if (!Schema::hasColumn('events', 'organizer_logo_url')) {
                $table->string('organizer_logo_url', 255)->nullable()->after('organizer_contact_name');
            }
            if (!Schema::hasColumn('events', 'organizer_website')) {
                $table->string('organizer_website', 255)->nullable()->after('organizer_logo_url');
            }
            if (!Schema::hasColumn('events', 'organizer_instagram_url')) {
                $table->string('organizer_instagram_url', 255)->nullable()->after('organizer_website');
            }
            if (!Schema::hasColumn('events', 'organizer_facebook_url')) {
                $table->string('organizer_facebook_url', 255)->nullable()->after('organizer_instagram_url');
            }
            if (!Schema::hasColumn('events', 'organizer_tiktok_url')) {
                $table->string('organizer_tiktok_url', 255)->nullable()->after('organizer_facebook_url');
            }
            if (!Schema::hasColumn('events', 'organizer_x_url')) {
                $table->string('organizer_x_url', 255)->nullable()->after('organizer_tiktok_url');
            }
            if (!Schema::hasColumn('events', 'organizer_whatsapp')) {
                $table->string('organizer_whatsapp', 50)->nullable()->after('organizer_x_url');
            }
            if (!Schema::hasColumn('events', 'organizer_email')) {
                $table->string('organizer_email', 150)->nullable()->after('organizer_whatsapp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $columns = [
                'organizer_company_name',
                'organizer_contact_name',
                'organizer_logo_url',
                'organizer_website',
                'organizer_instagram_url',
                'organizer_facebook_url',
                'organizer_tiktok_url',
                'organizer_x_url',
                'organizer_whatsapp',
                'organizer_email',
            ];

            $existingColumns = array_values(array_filter(
                $columns,
                fn(string $column): bool => Schema::hasColumn('events', $column)
            ));

            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
