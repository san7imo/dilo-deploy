<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Event;
use App\Models\User;

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
            'personalExpenses' => fn($q) => $q
                ->orderBy('expense_date', 'desc')
                ->orderBy('created_at', 'desc'),
        ]);

        return [
            'event' => $event,
            'finance' => $this->computeTotals($event),
        ];
    }

    /**
     * Datos financieros para road manager (solo registros propios).
     */
    public function roadManagerFinance(Event $event, User $user): array
    {
        $event->load([
            'mainArtist:id,name',
            'roadManagers:id,name,email',
            'payments' => fn($q) => $q
                ->where('created_by', $user->id)
                ->orderBy('payment_date', 'desc')
                ->orderBy('created_at', 'desc'),
            'expenses' => fn($q) => $q
                ->where('created_by', $user->id)
                ->orderBy('expense_date', 'desc')
                ->orderBy('created_at', 'desc'),
        ]);

        return [
            'event' => $event,
            'finance' => $this->computeRoadManagerTotals($event),
        ];
    }

    /**
     * Resumen y eventos para finanzas del artista.
     */
    public function artistOverview(Artist $artist, int $perPage = 10): array
    {
        $baseQuery = $artist->mainEvents()
            ->select('id', 'title', 'event_date', 'location', 'is_paid')
            ->withSum('payments as total_paid_base', 'amount_base')
            ->withSum(
                ['payments as advance_paid_base' => function ($query) {
                    $query->where('is_advance', true);
                }],
                'amount_base'
            )
            ->withSum('expenses as total_expenses_base', 'amount_base')
            ->withSum('personalExpenses as total_personal_expenses_base', 'amount_base')
            ->orderBy('event_date', 'desc');

        $summaryEvents = (clone $baseQuery)->get();
        $summary = $this->buildSummary($summaryEvents);

        $mapEvent = function ($event) {
            $totalPaid = round($event->total_paid_base ?? 0, 2);
            $advancePaid = round($event->advance_paid_base ?? 0, 2);
            $expenses = round($event->total_expenses_base ?? 0, 2);
            $personalExpenses = round($event->total_personal_expenses_base ?? 0, 2);
            $net = $totalPaid - $expenses;
            $shareArtist = round($net * 0.70, 2);

            return [
                'id' => $event->id,
                'title' => $event->title,
                'event_date' => $event->event_date?->toDateString(),
                'location' => $event->location,
                'total_paid_base' => $totalPaid,
                'advance_paid_base' => $advancePaid,
                'total_expenses_base' => $expenses,
                'total_personal_expenses_base' => $personalExpenses,
                'net_base' => round($net, 2),
                'artist_share_estimated_base' => $shareArtist,
                'artist_share_after_personal_base' => max(round($shareArtist - $personalExpenses, 2), 0),
                'label_share_estimated_base' => round($net * 0.30, 2),
                'status' => $event->is_paid ? 'pagado' : 'pendiente',
                'is_upcoming' => $event->event_date?->isFuture(),
            ];
        };

        $eventsPayload = (clone $baseQuery)
            ->paginate($perPage)
            ->through($mapEvent);

        $eventsAll = $summaryEvents->map($mapEvent);

        return [
            'summary' => $summary,
            'events' => $eventsPayload,
            'eventsAll' => $eventsAll,
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
    public function artistEventsList(Artist $artist, int $perPage = 10)
    {
        return $artist->mainEvents()
            ->select('id', 'title', 'event_date', 'location', 'is_paid')
            ->withSum('payments as total_paid_base', 'amount_base')
            ->withSum(
                ['payments as advance_paid_base' => function ($query) {
                    $query->where('is_advance', true);
                }],
                'amount_base'
            )
            ->withSum('expenses as total_expenses_base', 'amount_base')
            ->withSum('personalExpenses as total_personal_expenses_base', 'amount_base')
            ->orderBy('event_date', 'desc')
            ->paginate($perPage)
            ->through(function ($event) {
                $totalPaid = round($event->total_paid_base ?? 0, 2);
                $totalExpenses = round($event->total_expenses_base ?? 0, 2);
                $net = $totalPaid - $totalExpenses;
                $personalExpenses = round($event->total_personal_expenses_base ?? 0, 2);
                $shareArtist = round($net * 0.70, 2);

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'event_date' => $event->event_date?->toDateString(),
                    'location' => $event->location,
                    'total_paid_base' => $totalPaid,
                    'advance_paid_base' => round($event->advance_paid_base ?? 0, 2),
                    'total_expenses_base' => $totalExpenses,
                    'total_personal_expenses_base' => $personalExpenses,
                    'net_base' => round($net, 2),
                    'artist_share_estimated_base' => $shareArtist,
                    'artist_share_after_personal_base' => max(round($shareArtist - $personalExpenses, 2), 0),
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
            'personalExpenses:id,event_id,artist_id,amount_base,amount_original,currency,exchange_rate_to_base,expense_date,expense_type,name,description,payment_method,recipient',
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

        $eventPayload['personal_expenses'] = $event->personalExpenses->map(function ($e) {
            return [
                'id' => $e->id,
                'event_id' => $e->event_id,
                'artist_id' => $e->artist_id,
                'amount_base' => is_null($e->amount_base) ? 0 : (float) $e->amount_base,
                'amount_original' => is_null($e->amount_original) ? 0 : (float) $e->amount_original,
                'currency' => $e->currency,
                'exchange_rate_to_base' => is_null($e->exchange_rate_to_base) ? 1 : (float) $e->exchange_rate_to_base,
                'expense_date' => $e->expense_date?->toDateString(),
                'expense_type' => $e->expense_type,
                'name' => $e->name,
                'description' => $e->description,
                'payment_method' => $e->payment_method,
                'recipient' => $e->recipient,
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
                'total_personal_expenses_base' => $totals['total_personal_expenses_base'],
                'artist_share_after_personal_base' => $totals['share_artist_after_personal'],
                'label_share_estimated_base' => $totals['share_label'],
            ],
        ];
    }

    protected function buildSummary($events): array
    {
        $totalPaid = $events->sum('total_paid_base');
        $totalExpenses = $events->sum('total_expenses_base');
        $totalPersonalExpenses = $events->sum('total_personal_expenses_base');
        $net = $totalPaid - $totalExpenses;
        $shareArtist = round($net * 0.70, 2);

        return [
            'currency' => 'USD',
            'events_count' => $events->count(),
            'upcoming_events_count' => $events->filter(fn($event) => $event->event_date?->isFuture())->count(),
            'paid_events_count' => $events->filter(fn($event) => $event->is_paid)->count(),
            'total_paid_base' => round($totalPaid, 2),
            'total_expenses_base' => round($totalExpenses, 2),
            'total_personal_expenses_base' => round($totalPersonalExpenses, 2),
            'net_base' => round($net, 2),
            'artist_share_estimated_base' => $shareArtist,
            'artist_share_after_personal_base' => max(round($shareArtist - $totalPersonalExpenses, 2), 0),
            'label_share_estimated_base' => round($net * 0.30, 2),
        ];
    }

    protected function computeTotals(Event $event): array
    {
        $totalPaid = $event->payments->sum('amount_base');
        $advancePaid = $event->payments->where('is_advance', true)->sum('amount_base');
        $totalExpenses = $event->expenses->sum('amount_base');
        $totalPersonalExpenses = $event->relationLoaded('personalExpenses')
            ? $event->personalExpenses->sum('amount_base')
            : $event->personalExpenses()->sum('amount_base');
        $net = $totalPaid - $totalExpenses;
        $shareArtist = round($net * 0.70, 2);

        return [
            'total_paid_base' => round($totalPaid, 2),
            'advance_paid_base' => round($advancePaid, 2),
            'total_expenses_base' => round($totalExpenses, 2),
            'total_personal_expenses_base' => round($totalPersonalExpenses, 2),
            'net_base' => round($net, 2),
            'share_artist' => $shareArtist,
            'share_artist_after_personal' => max(round($shareArtist - $totalPersonalExpenses, 2), 0),
            'share_label' => round($net * 0.30, 2),
        ];
    }

    protected function computeRoadManagerTotals(Event $event): array
    {
        $totalPaid = $event->payments->sum('amount_base');
        $totalExpenses = $event->expenses->sum('amount_base');
        $net = $totalPaid - $totalExpenses;

        return [
            'total_paid_base' => round($totalPaid, 2),
            'advance_paid_base' => 0,
            'total_expenses_base' => round($totalExpenses, 2),
            'total_personal_expenses_base' => 0,
            'net_base' => round($net, 2),
            'share_artist' => 0,
            'share_artist_after_personal' => 0,
            'share_label' => 0,
        ];
    }
}
