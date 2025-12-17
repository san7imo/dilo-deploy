<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FinanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $artist = $user->artist()->firstOrFail();

        $events = $artist->mainEvents()
            ->select('id', 'title', 'event_date', 'location', 'is_paid')
            ->withSum('payments as total_paid_base', 'amount_base')
            ->withSum(
                ['payments as advance_paid_base' => function ($query) {
                    $query->where('is_advance', true);
                }],
                'amount_base'
            )
            ->orderBy('event_date', 'desc')
            ->get();

        $summary = [
            'currency' => 'EUR',
            'events_count' => $events->count(),
            'upcoming_events_count' => $events->filter(fn ($event) => $event->event_date?->isFuture())->count(),
            'paid_events_count' => $events->filter(fn ($event) => $event->is_paid)->count(),
            'total_paid_base' => round($events->sum('total_paid_base'), 2),
            'artist_share_estimated_base' => round(
                $events->sum(fn ($event) => ($event->total_paid_base ?? 0) * 0.70),
                2
            ),
        ];

        $eventsPayload = $events->map(function ($event) {
            $totalPaid = round($event->total_paid_base ?? 0, 2);
            $advancePaid = round($event->advance_paid_base ?? 0, 2);

            return [
                'id' => $event->id,
                'title' => $event->title,
                'event_date' => $event->event_date?->toDateString(),
                'location' => $event->location,
                'total_paid_base' => $totalPaid,
                'advance_paid_base' => $advancePaid,
                'artist_share_estimated_base' => round($totalPaid * 0.70, 2),
                'status' => $event->is_paid ? 'pagado' : 'pendiente',
                'is_upcoming' => $event->event_date?->isFuture(),
            ];
        });

        return Inertia::render('Artist/Finances/Index', [
            'summary' => $summary,
            'events' => $eventsPayload,
        ]);
    }
}
