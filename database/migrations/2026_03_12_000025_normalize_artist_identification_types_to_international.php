<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('identification_type', 'cc')
            ->update(['identification_type' => 'national_id']);

        DB::table('users')
            ->where('identification_type', 'ti')
            ->update(['identification_type' => 'national_id']);

        DB::table('users')
            ->where('identification_type', 'ce')
            ->update(['identification_type' => 'residence_permit']);

        DB::table('users')
            ->where('identification_type', 'nit')
            ->update(['identification_type' => 'tax_id']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('identification_type', 'national_id')
            ->update(['identification_type' => 'cc']);

        DB::table('users')
            ->where('identification_type', 'residence_permit')
            ->update(['identification_type' => 'ce']);

        DB::table('users')
            ->where('identification_type', 'tax_id')
            ->update(['identification_type' => 'nit']);
    }
};

