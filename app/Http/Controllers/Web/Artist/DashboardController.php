<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\EventFinanceAggregator;

class DashboardController extends Controller
{
    public function index(EventFinanceAggregator $financeAggregator)
    {
        $user = Auth::user();
        $artist = $user->artist()->firstOrFail();

        $eventsPayload = $financeAggregator->artistDashboardEvents($artist);
        $upcomingCount = $eventsPayload->count();

        return response()->json([
            'events' => $eventsPayload,
            'upcoming_events_count' => $upcomingCount,
        ]);
    }
}
