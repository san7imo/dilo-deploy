<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        $hasCountry = Schema::hasColumn('events', 'country');
        $hasCity = Schema::hasColumn('events', 'city');

        if (!$hasCountry && !$hasCity) {
            return;
        }

        $columns = ['id'];
        if ($hasCountry) {
            $columns[] = 'country';
        }
        if ($hasCity) {
            $columns[] = 'city';
        }

        $normalize = static function (?string $value): ?string {
            if ($value === null) {
                return null;
            }

            $value = preg_replace('/\s+/u', ' ', trim($value));
            if ($value === '') {
                return null;
            }

            return mb_convert_case(
                mb_strtolower($value, 'UTF-8'),
                MB_CASE_TITLE,
                'UTF-8'
            );
        };

        DB::table('events')
            ->select($columns)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($hasCountry, $hasCity, $normalize): void {
                foreach ($rows as $row) {
                    $updates = [];

                    if ($hasCountry) {
                        $normalizedCountry = $normalize($row->country);
                        if ($normalizedCountry !== $row->country) {
                            $updates['country'] = $normalizedCountry;
                        }
                    }

                    if ($hasCity) {
                        $normalizedCity = $normalize($row->city);
                        if ($normalizedCity !== $row->city) {
                            $updates['city'] = $normalizedCity;
                        }
                    }

                    if (!empty($updates)) {
                        DB::table('events')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        // No-op: no es seguro revertir normalización de texto histórica.
    }
};

