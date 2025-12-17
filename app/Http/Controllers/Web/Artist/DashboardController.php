<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $artist = $user->artist()->firstOrFail();

        $events = $artist->mainEvents()
            ->select('id', 'title', 'event_date', 'location', 'is_paid')
            ->whereNotNull('event_date')
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc')
            ->limit(6)
            ->get();

        $eventsPayload = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'event_date' => $event->event_date?->toDateString(),
                'location' => $event->location,
                'status' => $event->is_paid ? 'pagado' : 'pendiente',
                'is_upcoming' => true,
            ];
        });

        return response()->json([
            'events' => $eventsPayload,
            'upcoming_events_count' => $events->count(),
        ]);
    }
}
