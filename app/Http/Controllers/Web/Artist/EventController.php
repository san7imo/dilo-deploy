<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\EventFinanceAggregator;

class EventController extends Controller
{
    public function index(EventFinanceAggregator $financeAggregator)
    {
        $user = Auth::user();
        $artist = $user->artist()->firstOrFail();

        $events = $financeAggregator->artistEventsList($artist);

        return Inertia::render('Artist/Events/Index', [
            'events' => $events,
        ]);
    }

    public function show($id, EventFinanceAggregator $financeAggregator)
    {
        $user = Auth::user();
        $artist = $user->artist()->firstOrFail();

        $event = $artist->mainEvents()->findOrFail($id);
        $payload = $financeAggregator->artistEventFinance($event);

        return Inertia::render('Artist/Events/Show', [
            'event' => $payload['event'],
            'finance' => $payload['finance'],
        ]);
    }
}
