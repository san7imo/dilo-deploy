<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('audit.view');

        $filters = [
            'search' => trim((string) $request->string('search', '')),
            'event' => trim((string) $request->string('event', '')),
            'model' => trim((string) $request->string('model', '')),
            'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : null,
        ];

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('auditable_type', 'like', "%{$search}%")
                        ->orWhere('auditable_id', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['event'] !== '', fn($query) => $query->where('event', $filters['event']))
            ->when($filters['model'] !== '', fn($query) => $query->where('auditable_type', $filters['model']))
            ->when($filters['user_id'], fn($query, $userId) => $query->where('user_id', $userId))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(function (AuditLog $log): array {
                $oldValues = is_array($log->old_values) ? $log->old_values : [];
                $newValues = is_array($log->new_values) ? $log->new_values : [];
                $changedFields = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
                sort($changedFields);

                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'event_label' => $this->eventLabel($log->event),
                    'auditable_type' => $log->auditable_type,
                    'auditable_model' => class_basename($log->auditable_type),
                    'auditable_id' => $log->auditable_id,
                    'user' => $log->user
                        ? [
                            'id' => $log->user->id,
                            'name' => $log->user->name,
                            'email' => $log->user->email,
                        ]
                        : null,
                    'created_at' => $log->created_at,
                    'method' => $log->method,
                    'url' => $log->url,
                    'ip_address' => $log->ip_address,
                    'changed_fields' => array_slice($changedFields, 0, 8),
                    'changed_fields_total' => count($changedFields),
                ];
            });

        $models = AuditLog::query()
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(fn(string $type): array => [
                'value' => $type,
                'label' => class_basename($type),
            ])
            ->values();

        $users = User::query()
            ->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn(User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'events' => [
                ['value' => 'created', 'label' => 'Creado'],
                ['value' => 'updated', 'label' => 'Editado'],
                ['value' => 'deleted', 'label' => 'Eliminado'],
                ['value' => 'restored', 'label' => 'Restaurado'],
                ['value' => 'force_deleted', 'label' => 'Eliminado permanente'],
            ],
            'models' => $models,
            'users' => $users,
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        Gate::authorize('audit.view');

        $auditLog->load('user:id,name,email');

        $oldValues = is_array($auditLog->old_values) ? $auditLog->old_values : [];
        $newValues = is_array($auditLog->new_values) ? $auditLog->new_values : [];
        $fields = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
        sort($fields);

        $changes = collect($fields)->map(function (string $field) use ($oldValues, $newValues): array {
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;

            return [
                'field' => $field,
                'old_value' => $this->displayAuditValue($oldValue),
                'new_value' => $this->displayAuditValue($newValue),
                'changed' => $oldValue !== $newValue,
            ];
        })->values();

        return Inertia::render('Admin/AuditLogs/Show', [
            'log' => [
                'id' => $auditLog->id,
                'event' => $auditLog->event,
                'event_label' => $this->eventLabel($auditLog->event),
                'auditable_type' => $auditLog->auditable_type,
                'auditable_model' => class_basename($auditLog->auditable_type),
                'auditable_id' => $auditLog->auditable_id,
                'user' => $auditLog->user
                    ? [
                        'id' => $auditLog->user->id,
                        'name' => $auditLog->user->name,
                        'email' => $auditLog->user->email,
                    ]
                    : null,
                'created_at' => $auditLog->created_at,
                'method' => $auditLog->method,
                'url' => $auditLog->url,
                'ip_address' => $auditLog->ip_address,
                'user_agent' => $auditLog->user_agent,
            ],
            'changes' => $changes,
        ]);
    }

    private function eventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Creado',
            'updated' => 'Editado',
            'deleted' => 'Eliminado',
            'restored' => 'Restaurado',
            'force_deleted' => 'Eliminado permanente',
            default => Str::title(str_replace('_', ' ', $event)),
        };
    }

    private function displayAuditValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[valor no serializable]';
    }
}
