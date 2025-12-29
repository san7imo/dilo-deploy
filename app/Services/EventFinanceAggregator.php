<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Event;

class EventFinanceAggregator
{
    /**
     * Datos financieros completos para admin (evento).
     */
    public function adminFinance(Event $event): array
    {
        $event->load([
            'mainArtist:id,name',
            'roadManagers:id,name,email',
            'payments' => fn($q) => $q
                ->orderBy('payment_date', 'desc')
                ->orderBy('created_at', 'desc'),
            'expenses' => fn($q) => $q
                ->orderBy('expense_date', 'desc')
                ->orderBy('created_at', 'desc'),
        ]);

        return [
            'event' => $event,
            'finance' => $this->computeTotals($event),
        ];
    }

    /**
     * Resumen y eventos para finanzas del artista.
     */
    public function artistOverview(Artist $artist): array
    {
        $events = $artist->mainEvents()
            ->select('id', 'title', 'event_date', 'location', 'is_paid')
            ->withSum('payments as total_paid_base', 'amount_base')
            ->withSum(
                ['payments as advance_paid_base' => function ($query) {
                    $query->where('is_advance', true);
                }],
                'amount_base'
            )
            ->withSum('expenses as total_expenses_base', 'amount_base')
            ->orderBy('event_date', 'desc')
            ->get();

        $summary = $this->buildSummary($events);

        $eventsPayload = $events->map(function ($event) {
            $totalPaid = round($event->total_paid_base ?? 0, 2);
            $advancePaid = round($event->advance_paid_base ?? 0, 2);
            $expenses = round($event->total_expenses_base ?? 0, 2);
            $net = $totalPaid - $expenses;

            return [
                'id' => $event->id,
                'title' => $event->title,
                'event_date' => $event->event_date?->toDateString(),
                'location' => $event->location,
                'total_paid_base' => $totalPaid,
                'advance_paid_base' => $advancePaid,
                'total_expenses_base' => $expenses,
                'net_base' => round($net, 2),
                'artist_share_estimated_base' => round($net * 0.70, 2),
                'label_share_estimated_base' => round($net * 0.30, 2),
                'status' => $event->is_paid ? 'pagado' : 'pendiente',
                'is_upcoming' => $event->event_date?->isFuture(),
            ];
        });

        return [
            'summary' => $summary,
            'events' => $eventsPayload,
        ];
    }

    /**
     * Eventos próximos para el dashboard del artista.
     */
    public function artistDashboardEvents(Artist $artist, int $limit = 6)
    {
        return $artist->mainEvents()
            ->select('id', 'title', 'event_date', 'location', 'is_paid')
            ->whereNotNull('event_date')
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'event_date' => $event->event_date?->toDateString(),
                    'location' => $event->location,
                    'status' => $event->is_paid ? 'pagado' : 'pendiente',
                    'is_upcoming' => true,
                ];
            });
    }

    /**
     * Listado de eventos con finanzas para el index del artista.
     */
    public function artistEventsList(Artist $artist, int $limit = 20)
    {
        $events = $artist->mainEvents()
            ->select('id', 'title', 'event_date', 'location', 'is_paid')
            ->withSum('payments as total_paid_base', 'amount_base')
            ->withSum(
                ['payments as advance_paid_base' => function ($query) {
                    $query->where('is_advance', true);
                }],
                'amount_base'
            )
            ->withSum('expenses as total_expenses_base', 'amount_base')
            ->orderBy('event_date', 'desc')
            ->take($limit)
            ->get();

        return $events->map(function ($event) {
            $totalPaid = round($event->total_paid_base ?? 0, 2);
            $totalExpenses = round($event->total_expenses_base ?? 0, 2);
            $net = $totalPaid - $totalExpenses;

            return [
                'id' => $event->id,
                'title' => $event->title,
                'event_date' => $event->event_date?->toDateString(),
                'location' => $event->location,
                'total_paid_base' => $totalPaid,
                'advance_paid_base' => round($event->advance_paid_base ?? 0, 2),
                'total_expenses_base' => $totalExpenses,
                'net_base' => round($net, 2),
                'artist_share_estimated_base' => round($net * 0.70, 2),
                'label_share_estimated_base' => round($net * 0.30, 2),
                'status' => $event->is_paid ? 'pagado' : 'pendiente',
                'is_upcoming' => $event->event_date?->isFuture(),
            ];
        });
    }

    /**
     * Totales de un evento para show del artista.
     */
    public function artistEventFinance(Event $event): array
    {
        $event->load([
            'artists:id,name',
            // Load original amounts, currency and dates so frontend can display them
            'payments:id,event_id,amount_base,amount_original,currency,payment_date,is_advance',
            'expenses:id,event_id,amount_base,amount_original,currency,expense_date,name,description,category',
        ]);

        $totals = $this->computeTotals($event);
        // Normalize event payload: convert dates to simple ISO strings (no microseconds)
        $eventPayload = $event->toArray();
        $eventPayload['event_date'] = $event->event_date?->toDateString();

        $eventPayload['payments'] = $event->payments->map(function ($p) {
            return [
                'id' => $p->id,
                'event_id' => $p->event_id,
                'amount_base' => is_null($p->amount_base) ? 0 : (float) $p->amount_base,
                'amount_original' => is_null($p->amount_original) ? 0 : (float) $p->amount_original,
                'currency' => $p->currency,
                'payment_date' => $p->payment_date?->toDateString(),
                'is_advance' => (bool) $p->is_advance,
            ];
        })->toArray();

        $eventPayload['expenses'] = $event->expenses->map(function ($e) {
            return [
                'id' => $e->id,
                'event_id' => $e->event_id,
                'amount_base' => is_null($e->amount_base) ? 0 : (float) $e->amount_base,
                'amount_original' => is_null($e->amount_original) ? 0 : (float) $e->amount_original,
                'currency' => $e->currency,
                'expense_date' => $e->expense_date?->toDateString(),
                'name' => $e->name,
                'description' => $e->description,
                'category' => $e->category,
            ];
        })->toArray();

        return [
            'event' => $eventPayload,
            'finance' => [
                'total_paid_base' => $totals['total_paid_base'],
                'advance_paid_base' => $totals['advance_paid_base'],
                'total_expenses_base' => $totals['total_expenses_base'],
                'net_base' => $totals['net_base'],
                'artist_share_estimated_base' => $totals['share_artist'],
                'label_share_estimated_base' => $totals['share_label'],
            ],
        ];
    }

    protected function buildSummary($events): array
    {
        $totalPaid = $events->sum('total_paid_base');
        $totalExpenses = $events->sum('total_expenses_base');
        $net = $totalPaid - $totalExpenses;

        return [
            'currency' => 'USD',
            'events_count' => $events->count(),
            'upcoming_events_count' => $events->filter(fn($event) => $event->event_date?->isFuture())->count(),
            'paid_events_count' => $events->filter(fn($event) => $event->is_paid)->count(),
            'total_paid_base' => round($totalPaid, 2),
            'total_expenses_base' => round($totalExpenses, 2),
            'net_base' => round($net, 2),
            'artist_share_estimated_base' => round($net * 0.70, 2),
            'label_share_estimated_base' => round($net * 0.30, 2),
        ];
    }

    protected function computeTotals(Event $event): array
    {
        $totalPaid = $event->payments->sum('amount_base');
        $advancePaid = $event->payments->where('is_advance', true)->sum('amount_base');
        $totalExpenses = $event->expenses->sum('amount_base');
        $net = $totalPaid - $totalExpenses;

        return [
            'total_paid_base' => round($totalPaid, 2),
            'advance_paid_base' => round($advancePaid, 2),
            'total_expenses_base' => round($totalExpenses, 2),
            'net_base' => round($net, 2),
            'share_artist' => round($net * 0.70, 2),
            'share_label' => round($net * 0.30, 2),
        ];
    }
}
