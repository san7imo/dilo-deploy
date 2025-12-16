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

        $events = $artist->mainEvents()
            ->select('id', 'title', 'event_date', 'location')
            ->orderBy('event_date', 'asc')
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

        $event = $artist->mainEvents()
            ->with(['artists:id,name'])
            ->findOrFail($id);

        return Inertia::render('Artist/Events/Show', [
            'event' => $event,
        ]);
    }
}
