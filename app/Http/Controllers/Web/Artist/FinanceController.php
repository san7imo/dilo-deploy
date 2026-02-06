<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\EventFinanceAggregator;

class FinanceController extends Controller
{
    public function index(EventFinanceAggregator $financeAggregator)
    {
        $user = Auth::user();
        $artist = $user->artist()->firstOrFail();

        $payload = $financeAggregator->artistOverview($artist);

        return Inertia::render('Artist/Finances/Index', [
            'summary' => $payload['summary'],
            'events' => $payload['events'],
            'eventsAll' => $payload['eventsAll'],
        ]);
    }
}
