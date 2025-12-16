<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $artist = $user->artist()->firstOrFail();

        $events = $artist->events()
            ->select('events.id', 'events.title', 'events.event_date', 'events.location')
            ->orderBy('events.event_date', 'asc')
            ->take(20)
            ->get();

        return Inertia::render('Artist/Events/Index', [
            'events' => $events,
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();
        $artist = $user->artist()->firstOrFail();

        $event = $artist->events()
            ->with(['artists' => function ($query) {
                $query->select('artists.id', 'artists.name');
            }])
            ->findOrFail($id);

        return Inertia::render('Artist/Events/Show', [
            'event' => $event,
        ]);
    }
}
