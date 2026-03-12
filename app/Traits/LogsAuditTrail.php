<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Throwable;

trait LogsAuditTrail
{
    protected array $auditOriginalValues = [];

    protected static function bootLogsAuditTrail(): void
    {
        $usesSoftDeletes = in_array(
            SoftDeletes::class,
            class_uses_recursive(static::class),
            true
        );

        static::updating(function (Model $model): void {
            $model->auditOriginalValues = $model->getOriginal();
        });

        static::created(function (Model $model): void {
            $model->recordAuditEvent('created', null, $model->auditAttributes($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            $changes = Arr::except($model->getChanges(), ['updated_at']);

            if (empty($changes)) {
                return;
            }

            $before = Arr::only(
                $model->auditOriginalValues ?: $model->getOriginal(),
                array_keys($changes)
            );
            $after = Arr::only($model->getAttributes(), array_keys($changes));

            $model->recordAuditEvent(
                'updated',
                $model->auditAttributes($before),
                $model->auditAttributes($after)
            );
        });

        static::deleted(function (Model $model): void {
            $event = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
                ? 'force_deleted'
                : 'deleted';

            $model->recordAuditEvent($event, $model->auditAttributes($model->getOriginal()), null);
        });

        if ($usesSoftDeletes) {
            static::restored(function (Model $model): void {
                $model->recordAuditEvent('restored', null, $model->auditAttributes($model->getAttributes()));
            });
        }
    }

    protected function recordAuditEvent(string $event, ?array $oldValues, ?array $newValues): void
    {
        if ($this instanceof AuditLog) {
            return;
        }

        try {
            $request = app()->bound('request') ? request() : null;

            AuditLog::create([
                'auditable_type' => $this->getMorphClass(),
                'auditable_id' => (string) $this->getKey(),
                'event' => $event,
                'user_id' => auth()->id(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    protected function auditAttributes(array $attributes): array
    {
        $filtered = Arr::except($attributes, $this->getHidden());
        ksort($filtered);

        return $filtered;
    }
}
