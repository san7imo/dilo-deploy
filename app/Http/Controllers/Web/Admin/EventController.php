<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\EventService;
use App\Models\Event;
use App\Models\Artist;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $events = $this->eventService->getAll(10);

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
        ]);
    }

    /** 🆕 Formulario de creación */
    public function create()
    {
        $artists = Artist::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Events/Create', [
            'artists' => $artists,
        ]);
    }

    /** 💾 Guardar nuevo evento */
    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();

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

        return Inertia::render('Admin/Events/Edit', [
            'event' => $event->load('artists'),
            'artists' => $artists,
        ]);
    }

    /** 🔄 Actualizar evento */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->validated();

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
