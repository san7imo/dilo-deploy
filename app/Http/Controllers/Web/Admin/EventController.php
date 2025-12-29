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
        $events = $this->eventService->getVisibleForUser($user, 10);
        $canManageEvents = (bool) $user?->hasRole('admin');

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
        ]);
    }

    /** 🆕 Formulario de creación */
    public function create()
    {
        $artists = Artist::select('id', 'name')->orderBy('name')->get();
        $roadManagers = User::role('roadmanager')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Events/Create', [
            'artists' => $artists,
            'roadManagers' => $roadManagers,
        ]);
    }

    /** 💾 Guardar nuevo evento */
    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();

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
        $artists = Artist::select('id', 'name')->orderBy('name')->get();
        $roadManagers = User::role('roadmanager')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Events/Edit', [
            'event' => $event->load('artists', 'roadManagers'),
            'artists' => $artists,
            'roadManagers' => $roadManagers,
        ]);
    }

    /** 🔄 Actualizar evento */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->validated();

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
        $this->eventService->delete($event);

        return redirect()->route('admin.events.index')
            ->with('success', 'Evento eliminado correctamente');
    }
}
