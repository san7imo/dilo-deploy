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
            ->with([
                'artists:id,name',
                'payments:id,event_id,amount_base,is_advance',
            ])
            ->findOrFail($id);

        $totalPaid = $event->payments->sum('amount_base');
        $advancePaid = $event->payments->where('is_advance', true)->sum('amount_base');

        return Inertia::render('Artist/Events/Show', [
            'event' => $event,
            'finance' => [
                'total_paid_base' => round($totalPaid, 2),
                'advance_paid_base' => round($advancePaid, 2),
                'artist_share_estimated_base' => round($totalPaid * 0.70, 2), // por ahora sin gastos
            ],
        ]);
    }
}
