<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Artist;
use App\Models\Event;
use App\Models\EventExpense;
use App\Models\EventPayment;
use App\Models\EventPersonalExpense;
use App\Models\Organizer;
use App\Models\User;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EventController extends Controller
{
    protected EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /** 📋 Listado de eventos */
    public function index(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'artist_id' => ['nullable', 'integer', 'exists:artists,id'],
        ]);

        $artistId = !empty($validated['artist_id']) ? (int) $validated['artist_id'] : null;

        $dateTo = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : null;
        $dateFrom = !empty($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : null;

        $events = $this->hideFinanceForNonAdmin(
            $this->eventService->getVisibleForUser($user, 10, [
                'artist_id' => $artistId,
                'date_from' => $dateFrom?->toDateString(),
                'date_to' => $dateTo?->toDateString(),
            ]),
            $user
        );
        $canManageEvents = (bool) $user?->hasRole('admin')
            || (bool) $user?->hasRole('contentmanager')
            || (bool) $user?->hasRole('roadmanager');
        $canSeeFinance = (bool) $user?->hasRole('admin') || (bool) $user?->hasRole('roadmanager');

        if ($user?->hasRole('roadmanager')) {
            $artistIds = Event::query()
                ->whereHas('roadManagers', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->whereNotNull('main_artist_id')
                ->distinct()
                ->pluck('main_artist_id');

            $artists = Artist::select('id', 'name')
                ->whereIn('id', $artistIds)
                ->orderBy('name')
                ->get();
        } else {
            $artists = Artist::select('id', 'name')->orderBy('name')->get();
        }

        $analytics = null;
        if ($canSeeFinance) {
            $analyticsEvents = Event::query()
                ->visibleForUser($user)
                ->whereNotNull('event_date')
                ->when($dateFrom, fn($query) => $query->whereDate('event_date', '>=', $dateFrom->toDateString()))
                ->when($dateTo, fn($query) => $query->whereDate('event_date', '<=', $dateTo->toDateString()))
                ->when($artistId, fn($query) => $query->where('main_artist_id', $artistId))
                ->withSum('payments as total_paid_base', 'amount_base')
                ->orderBy('event_date', 'desc')
                ->get([
                    'id',
                    'event_date',
                    'country',
                    'city',
                    'event_type',
                    'organizer_company_name',
                    'organizer_contact_name',
                ]);

            $analytics = $this->buildEventAnalytics($analyticsEvents, $dateFrom, $dateTo);
        }

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'artists' => $artists,
            'canManageEvents' => $canManageEvents,
            'canSeeFinance' => $canSeeFinance,
            'analytics' => $analytics,
            'analyticsFilters' => [
                'date_from' => $dateFrom?->toDateString() ?? '',
                'date_to' => $dateTo?->toDateString() ?? '',
                'artist_id' => $artistId ?: 'todos',
            ],
        ]);
    }

    /** 🆕 Formulario de creación */
    public function create()
    {
        $user = request()->user();
        $artists = Artist::select('id', 'name')->orderBy('name')->get();
        $organizers = Organizer::query()
            ->select('id', 'company_name', 'contact_name', 'whatsapp', 'email', 'address', 'logo_url', 'website', 'instagram_url', 'facebook_url', 'tiktok_url', 'x_url')
            ->orderBy('company_name')
            ->get();
        if ($user?->hasRole('roadmanager')) {
            $roadManagers = User::query()
                ->whereKey($user->id)
                ->select('id', 'name', 'email')
                ->get();
        } else {
            $roadManagers = User::role('roadmanager')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Admin/Events/Create', [
            'artists' => $artists,
            'roadManagers' => $roadManagers,
            'organizers' => $organizers,
        ]);
    }

    /** 💾 Guardar nuevo evento */
    public function store(StoreEventRequest $request)
    {
        $user = $request->user();
        $data = $this->stripFinanceFields($request->validated(), $user);
        $data = $this->applyRoadManagerAssignmentOnCreate($data, $user);

        if (($data['status'] ?? null) === 'pagado') {
            throw ValidationException::withMessages([
                'status' => 'El total pagado debe ser igual o mayor al fee del show para marcarlo como pagado.',
            ]);
        }

        // Manejar archivos desde el form (poster o poster_file)
        if ($request->hasFile('poster')) {
            $data['poster_file'] = $request->file('poster');
        } elseif ($request->hasFile('poster_file')) {
            $data['poster_file'] = $request->file('poster_file');
        }

        Log::info('🎯 [EventController] Datos enviados al servicio (create)', [
            'keys' => array_keys($data),
            'files' => collect($data)->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)->keys()->toArray(),
        ]);

        $this->eventService->create($data);

        return redirect()->route('admin.events.index')
            ->with('success', 'Evento creado correctamente');
    }

    /** ✏️ Formulario de edición */
    public function edit(Event $event)
    {
        $user = request()->user();
        $this->denyIfRoadManagerNotAssigned($event, $user);
        $event = $this->hideFinanceForNonAdmin($event, request()->user());
        $artists = Artist::select('id', 'name')->orderBy('name')->get();
        $organizers = Organizer::query()
            ->select('id', 'company_name', 'contact_name', 'whatsapp', 'email', 'address', 'logo_url', 'website', 'instagram_url', 'facebook_url', 'tiktok_url', 'x_url')
            ->orderBy('company_name')
            ->get();
        if ($user?->hasRole('roadmanager')) {
            $roadManagers = User::query()
                ->whereKey($user->id)
                ->select('id', 'name', 'email')
                ->get();
        } else {
            $roadManagers = User::role('roadmanager')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Admin/Events/Edit', [
            'event' => $event->load('artists', 'roadManagers', 'organizer'),
            'artists' => $artists,
            'roadManagers' => $roadManagers,
            'organizers' => $organizers,
        ]);
    }

    /** 🔄 Actualizar evento */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $user = $request->user();
        $this->denyIfRoadManagerNotAssigned($event, $user);
        $data = $this->stripFinanceFields($request->validated(), $user);
        $data = $this->applyRoadManagerAssignmentOnUpdate($data, $user);

        $status = $data['status'] ?? null;
        $feeChanged = array_key_exists('show_fee_total', $data);

        if ($status === 'pagado' && ($event->status !== 'pagado' || $feeChanged)) {
            $feeTotal = (float) ($data['show_fee_total'] ?? $event->show_fee_total ?? 0);
            $totalPaid = (float) $event->payments()->sum('amount_base');

            if ($feeTotal <= 0 || $totalPaid < $feeTotal) {
                throw ValidationException::withMessages([
                    'status' => 'El total pagado debe ser igual o mayor al fee del show para marcarlo como pagado.',
                ]);
            }
        }

        if ($request->hasFile('poster')) {
            $data['poster_file'] = $request->file('poster');
        } elseif ($request->hasFile('poster_file')) {
            $data['poster_file'] = $request->file('poster_file');
        }

        Log::info('✏️ [EventController] Datos enviados al servicio (update)', [
            'event_id' => $event->id,
            'files' => collect($data)->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)->keys()->toArray(),
        ]);

        $this->eventService->update($event, $data);

        return redirect()->route('admin.events.index')
            ->with('success', 'Evento actualizado correctamente');
    }

    /** 🗑️ Eliminar evento */
    public function destroy(Event $event)
    {
        $this->denyIfRoadManagerNotAssigned($event, request()->user());

        DB::transaction(function () use ($event): void {
            EventPayment::query()
                ->where('event_id', $event->id)
                ->get()
                ->each
                ->delete();

            EventExpense::query()
                ->where('event_id', $event->id)
                ->get()
                ->each
                ->delete();

            EventPersonalExpense::query()
                ->where('event_id', $event->id)
                ->get()
                ->each
                ->delete();

            $this->eventService->delete($event);
        });

        return redirect()->route('admin.events.index')
            ->with('success', 'Evento eliminado correctamente');
    }

    public function trash(Request $request)
    {
        Gate::authorize('trash.view.events');

        $events = Event::onlyTrashed()
            ->with(['artists:id,name', 'mainArtist:id,name'])
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(fn(Event $event) => [
                'id' => $event->id,
                'primary' => $event->title,
                'secondary' => trim(($event->mainArtist?->name ?? '-') . ' · ' . ($event->event_date?->toDateString() ?? '-'), ' ·'),
                'deleted_at' => $event->deleted_at,
                'can_force_delete' => true,
                'force_delete_blocked_reason' => null,
            ]);

        if ($request->expectsJson()) {
            return response()->json($events);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Eventos',
            'items' => $events,
            'restoreRoute' => 'admin.events.restore',
            'forceDeleteRoute' => 'admin.events.force-delete',
            'backRoute' => 'admin.events.index',
        ]);
    }

    public function restore(int $eventId)
    {
        Gate::authorize('trash.manage.events');

        $event = Event::onlyTrashed()->findOrFail($eventId);

        if ($event->main_artist_id) {
            $mainArtist = Artist::withTrashed()->find($event->main_artist_id);
            if (!$mainArtist || $mainArtist->trashed()) {
                return back()->withErrors([
                    'event' => 'No se puede restaurar el evento porque su artista principal está eliminado. Restaura primero el artista.',
                ]);
            }
        }

        DB::transaction(function () use ($event): void {
            $event->restore();

            EventPayment::onlyTrashed()
                ->where('event_id', $event->id)
                ->restore();

            EventExpense::onlyTrashed()
                ->where('event_id', $event->id)
                ->restore();

            EventPersonalExpense::onlyTrashed()
                ->where('event_id', $event->id)
                ->restore();
        });

        return redirect()->route('admin.events.index')
            ->with('success', 'Evento restaurado correctamente');
    }

    public function forceDelete(int $eventId)
    {
        Gate::authorize('trash.manage.events');

        $event = Event::withTrashed()->findOrFail($eventId);

        if (!$event->trashed()) {
            return back()->withErrors([
                'event' => 'Solo puedes eliminar permanentemente eventos en papelera.',
            ]);
        }

        DB::transaction(function () use ($event): void {
            EventPayment::withTrashed()
                ->where('event_id', $event->id)
                ->get()
                ->each
                ->forceDelete();

            EventExpense::withTrashed()
                ->where('event_id', $event->id)
                ->get()
                ->each
                ->forceDelete();

            EventPersonalExpense::withTrashed()
                ->where('event_id', $event->id)
                ->get()
                ->each
                ->forceDelete();

            $event->artists()->detach();
            $event->roadManagers()->detach();
            $event->forceDelete();
        });

        return redirect()->route('admin.events.index')
            ->with('success', 'Evento eliminado permanentemente');
    }

    protected function stripFinanceFields(array $data, ?\App\Models\User $user): array
    {
        if ($user && $user->hasRole('admin')) {
            return $data;
        }

        $fields = [
            'show_fee_total',
            'currency',
            'advance_percentage',
            'artist_share_percentage',
            'label_share_percentage',
            'advance_expected',
            'full_payment_due_date',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    protected function hideFinanceForNonAdmin($payload, ?\App\Models\User $user)
    {
        if (!$user || $user->hasRole('admin')) {
            return $payload;
        }

        $hidden = [
            'show_fee_total',
            'currency',
            'advance_percentage',
            'artist_share_percentage',
            'label_share_percentage',
            'advance_expected',
            'full_payment_due_date',
            'total_paid_base',
            'advance_paid_base',
            'total_expenses_base',
            'net_base',
            'artist_share_estimated_base',
            'label_share_estimated_base',
        ];

        if ($payload instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $payload->getCollection()->transform(function ($event) use ($hidden) {
                return $event->makeHidden($hidden);
            });
            return $payload;
        }

        if ($payload instanceof Event) {
            return $payload->makeHidden($hidden);
        }

        return $payload;
    }

    protected function applyRoadManagerAssignmentOnCreate(array $data, ?\App\Models\User $user): array
    {
        if ($user && $user->hasRole('roadmanager')) {
            $data['road_manager_ids'] = [$user->id];
        }

        return $data;
    }

    protected function applyRoadManagerAssignmentOnUpdate(array $data, ?\App\Models\User $user): array
    {
        if ($user && $user->hasRole('roadmanager')) {
            unset($data['road_manager_ids']);
        }

        return $data;
    }

    protected function denyIfRoadManagerNotAssigned(Event $event, ?\App\Models\User $user): void
    {
        if (!$user || !$user->hasRole('roadmanager')) {
            return;
        }

        $assigned = $event->roadManagers()
            ->where('users.id', $user->id)
            ->exists();

        if (!$assigned) {
            abort(403);
        }
    }

    protected function buildEventAnalytics(Collection $events, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $latestEventDate = $events
            ->pluck('event_date')
            ->filter()
            ->map(fn($value) => $value instanceof Carbon ? $value : Carbon::parse($value))
            ->sortDesc()
            ->first();
        $oldestEventDate = $events
            ->pluck('event_date')
            ->filter()
            ->map(fn($value) => $value instanceof Carbon ? $value : Carbon::parse($value))
            ->sort()
            ->first();

        $effectiveTo = $dateTo?->copy()
            ?? ($latestEventDate ? $latestEventDate->copy()->endOfDay() : now()->endOfDay());
        $effectiveFrom = $dateFrom?->copy()
            ?? ($oldestEventDate ? $oldestEventDate->copy()->startOfDay() : $effectiveTo->copy()->startOfYear());

        $countrySummary = $events
            ->groupBy(fn($event) => $this->normalizeCountry($event->country))
            ->map(function (Collection $group, string $country) {
                return [
                    'country' => $country,
                    'events_count' => $group->count(),
                    'cities_count' => $group->groupBy(
                        fn($event) => $this->normalizeCity($event->city)
                    )->count(),
                    'total_income_usd' => round($group->sum(fn($event) => (float) ($event->total_paid_base ?? 0)), 2),
                    'avg_income_usd' => round($group->avg(fn($event) => (float) ($event->total_paid_base ?? 0)) ?: 0, 2),
                ];
            })
            ->sortByDesc('events_count')
            ->values();

        $citySummary = $events
            ->groupBy(function ($event) {
                $country = $this->normalizeCountry($event->country);
                $city = $this->normalizeCity($event->city);
                return "{$country}|{$city}";
            })
            ->map(function (Collection $group, string $key) {
                [$country, $city] = explode('|', $key, 2);
                return [
                    'country' => $country,
                    'city' => $city,
                    'events_count' => $group->count(),
                    'total_income_usd' => round($group->sum(fn($event) => (float) ($event->total_paid_base ?? 0)), 2),
                    'avg_income_usd' => round($group->avg(fn($event) => (float) ($event->total_paid_base ?? 0)) ?: 0, 2),
                ];
            })
            ->sortByDesc('events_count')
            ->values();

        $eventTypeSummary = $events
            ->groupBy(fn($event) => $this->normalizeEventType($event->event_type))
            ->map(function (Collection $group, string $eventType) {
                return [
                    'event_type' => $eventType,
                    'events_count' => $group->count(),
                    'total_income_usd' => round($group->sum(fn($event) => (float) ($event->total_paid_base ?? 0)), 2),
                    'avg_income_usd' => round($group->avg(fn($event) => (float) ($event->total_paid_base ?? 0)) ?: 0, 2),
                ];
            })
            ->sortByDesc('events_count')
            ->values();

        $organizerRanking = $events
            ->groupBy(function ($event) {
                $company = $this->normalizeText($event->organizer_company_name);
                $contact = $this->normalizeText($event->organizer_contact_name);

                if ($company !== null) {
                    return $company;
                }

                if ($contact !== null) {
                    return $contact;
                }

                return 'Sin organizador';
            })
            ->map(function (Collection $group, string $organizer) {
                return [
                    'organizer' => $organizer,
                    'events_count' => $group->count(),
                    'total_income_usd' => round($group->sum(fn($event) => (float) ($event->total_paid_base ?? 0)), 2),
                    'avg_income_usd' => round($group->avg(fn($event) => (float) ($event->total_paid_base ?? 0)) ?: 0, 2),
                ];
            })
            ->sort(function (array $a, array $b) {
                if ($a['events_count'] === $b['events_count']) {
                    return $b['total_income_usd'] <=> $a['total_income_usd'];
                }

                return $b['events_count'] <=> $a['events_count'];
            })
            ->values();

        $periodSummaries = [
            'monthly' => $this->buildPeriodSummary(
                $events,
                max($effectiveFrom->timestamp, $effectiveTo->copy()->startOfMonth()->startOfDay()->timestamp),
                $effectiveTo->timestamp
            ),
            'quarterly' => $this->buildPeriodSummary(
                $events,
                max($effectiveFrom->timestamp, $effectiveTo->copy()->firstOfQuarter()->startOfDay()->timestamp),
                $effectiveTo->timestamp
            ),
            'yearly' => $this->buildPeriodSummary(
                $events,
                max($effectiveFrom->timestamp, $effectiveTo->copy()->startOfYear()->startOfDay()->timestamp),
                $effectiveTo->timestamp
            ),
        ];

        return [
            'events_count' => $events->count(),
            'total_income_usd' => round($events->sum(fn($event) => (float) ($event->total_paid_base ?? 0)), 2),
            'country_summary' => $countrySummary,
            'city_summary' => $citySummary,
            'event_type_summary' => $eventTypeSummary,
            'organizer_ranking' => $organizerRanking,
            'period_summaries' => $periodSummaries,
        ];
    }

    protected function normalizeText(?string $value, ?string $fallback = null): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return $fallback;
        }

        return $normalized;
    }

    protected function normalizeCountry(?string $value): string
    {
        return $this->normalizeLocationLabel($value, 'Sin país');
    }

    protected function normalizeCity(?string $value): string
    {
        return $this->normalizeLocationLabel($value, 'Sin ciudad');
    }

    protected function normalizeLocationLabel(?string $value, string $fallback): string
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === null) {
            return $fallback;
        }

        $normalized = Str::of($normalized)
            ->ascii()
            ->squish()
            ->lower()
            ->toString();

        return mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    }

    protected function normalizeEventType(?string $eventType): string
    {
        $normalized = Str::of((string) $eventType)->trim()->lower()->toString();

        return match ($normalized) {
            'publico' => 'Publico',
            'masivo' => 'Masivo',
            'discoteca' => 'Discoteca',
            'privado' => 'Privado',
            'meet_and_greet' => 'Meet & Greet',
            'labor_social' => 'Labor social',
            '' => 'Sin formato',
            default => Str::of($normalized)->replace('_', ' ')->title()->toString(),
        };
    }

    protected function buildPeriodSummary(Collection $events, int $fromTimestamp, int $toTimestamp): array
    {
        $from = Carbon::createFromTimestamp($fromTimestamp)->startOfDay();
        $to = Carbon::createFromTimestamp($toTimestamp)->endOfDay();

        $eventsInWindow = $events->filter(function ($event) use ($from, $to) {
            if (!$event->event_date) {
                return false;
            }

            $eventDate = $event->event_date->copy()->startOfDay();

            return $eventDate->greaterThanOrEqualTo($from) && $eventDate->lessThanOrEqualTo($to);
        });

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'events_count' => $eventsInWindow->count(),
            'total_income_usd' => round($eventsInWindow->sum(fn($event) => (float) ($event->total_paid_base ?? 0)), 2),
            'avg_income_usd' => round($eventsInWindow->avg(fn($event) => (float) ($event->total_paid_base ?? 0)) ?: 0, 2),
        ];
    }
}
