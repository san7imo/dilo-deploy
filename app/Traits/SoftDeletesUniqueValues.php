<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait SoftDeletesUniqueValues
{
    protected static array $snapshotColumnExistsCache = [];

    protected static function bootSoftDeletesUniqueValues(): void
    {
        static::deleting(function (Model $model): void {
            if (!method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                return;
            }

            if (!$model->snapshotColumnExists()) {
                return;
            }

            $columns = $model->softDeleteUniqueColumns();
            if (empty($columns)) {
                return;
            }

            $snapshot = [];
            $updates = [];

            foreach ($columns as $column) {
                $value = $model->getAttribute($column);
                if ($value === null || $value === '') {
                    continue;
                }

                $snapshot[$column] = $value;
                $updates[$column] = $model->softDeleteUniquePlaceholder($column);
            }

            if (empty($updates)) {
                return;
            }

            $updates['deleted_unique_snapshot'] = json_encode($snapshot, JSON_UNESCAPED_UNICODE);

            DB::table($model->getTable())
                ->where($model->getKeyName(), $model->getKey())
                ->update($updates);

            $model->forceFill(array_merge($updates, [
                'deleted_unique_snapshot' => $snapshot,
            ]));
        });

        static::restoring(function (Model $model): void {
            if (!$model->snapshotColumnExists()) {
                return;
            }

            $snapshot = $model->getAttribute('deleted_unique_snapshot');
            if (empty($snapshot) || !is_array($snapshot)) {
                return;
            }

            foreach ($snapshot as $column => $originalValue) {
                if ($originalValue === null || $originalValue === '') {
                    continue;
                }

                $conflict = $model->newQuery()
                    ->where($column, $originalValue)
                    ->whereKeyNot($model->getKey())
                    ->exists();

                if ($conflict) {
                    throw ValidationException::withMessages([
                        $column => "No se puede restaurar: {$column} ya está en uso por un registro activo.",
                    ]);
                }

                $model->setAttribute($column, $originalValue);
            }

            $model->setAttribute('deleted_unique_snapshot', null);
        });
    }

    protected function softDeleteUniqueColumns(): array
    {
        return property_exists($this, 'softDeleteUniqueColumns')
            ? $this->softDeleteUniqueColumns
            : [];
    }

    protected function softDeleteUniquePlaceholder(string $column): string
    {
        $id = (string) $this->getKey();
        $suffix = Str::lower(Str::random(12));

        return "deleted_{$column}_{$id}_{$suffix}";
    }

    protected function snapshotColumnExists(): bool
    {
        $table = $this->getTable();

        if (!array_key_exists($table, self::$snapshotColumnExistsCache)) {
            self::$snapshotColumnExistsCache[$table] = Schema::hasColumn($table, 'deleted_unique_snapshot');
        }

        return self::$snapshotColumnExistsCache[$table];
    }
}
