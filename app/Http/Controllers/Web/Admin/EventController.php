<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\EventService;
use App\Models\Event;
use App\Models\Artist;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        $events = $this->hideFinanceForNonAdmin(
            $this->eventService->getVisibleForUser($user, 10),
            $user
        );
        $canManageEvents = (bool) $user?->hasRole('admin')
            || (bool) $user?->hasRole('contentmanager')
            || (bool) $user?->hasRole('roadmanager');

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

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'artists' => $artists,
            'canManageEvents' => $canManageEvents,
            'canSeeFinance' => (bool) $user?->hasRole('admin') || (bool) $user?->hasRole('roadmanager'),
        ]);
    }

    /** 🆕 Formulario de creación */
    public function create()
    {
        $user = request()->user();
        $artists = Artist::select('id', 'name')->orderBy('name')->get();
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
            'event' => $event->load('artists', 'roadManagers'),
            'artists' => $artists,
            'roadManagers' => $roadManagers,
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
        $this->eventService->delete($event);

        return redirect()->route('admin.events.index')
            ->with('success', 'Evento eliminado correctamente');
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
}
