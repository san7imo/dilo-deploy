<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Event;
use App\Models\Genre;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Retorna el resumen general del dashboard principal.
     */
    public function index()
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $yearStart = now()->startOfYear()->toDateString();

        $upcomingEvents = Event::query()
            ->whereNotNull('event_date')
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date', 'asc')
            ->take(5)
            ->get(['id', 'title', 'event_date', 'location', 'city', 'country']);

        $otherArtistsCount = 0;
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $otherArtistsCount = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'external_artist')
                        ->where('guard_name', 'web');
                })
                ->count();
        }

        $compositionsCount = Schema::hasTable('compositions')
            ? (int) DB::table('compositions')->count()
            : 0;

        $organizersCount = Schema::hasTable('organizers')
            ? (int) DB::table('organizers')->count()
            : 0;

        $workersCount = Schema::hasTable('workers')
            ? (int) DB::table('workers')->count()
            : 0;

        $eventsTotal = (int) Event::count();
        $eventsUpcomingCount = (int) Event::query()
            ->whereNotNull('event_date')
            ->whereDate('event_date', '>=', $today)
            ->count();
        $eventsThisMonthCount = (int) Event::query()
            ->whereNotNull('event_date')
            ->whereBetween('event_date', [$monthStart, $monthEnd])
            ->count();

        $distinctCountriesCount = (int) Event::query()
            ->whereNotNull('country')
            ->whereRaw("TRIM(country) <> ''")
            ->distinct('country')
            ->count('country');

        $distinctCitiesCount = (int) Event::query()
            ->whereNotNull('city')
            ->whereRaw("TRIM(city) <> ''")
            ->distinct('city')
            ->count('city');

        $topCountries = Event::query()
            ->selectRaw('LOWER(TRIM(country)) as country_key, COUNT(*) as total')
            ->whereNotNull('country')
            ->whereRaw("TRIM(country) <> ''")
            ->groupBy('country_key')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'label' => Str::title((string) $row->country_key),
                'total' => (int) $row->total,
            ])
            ->values();

        $topEventTypes = Event::query()
            ->selectRaw('LOWER(TRIM(event_type)) as event_type_key, COUNT(*) as total')
            ->whereNotNull('event_type')
            ->whereRaw("TRIM(event_type) <> ''")
            ->groupBy('event_type_key')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'label' => Str::title((string) $row->event_type_key),
                'total' => (int) $row->total,
            ])
            ->values();

        $eventIncomeUsd = 0.0;
        if (Schema::hasTable('event_payments')) {
            $eventIncomeUsd = (float) DB::table('event_payments')
                ->whereNull('deleted_at')
                ->sum(Schema::hasColumn('event_payments', 'amount_base') ? 'amount_base' : 'amount_original');
        }

        $eventExpensesUsd = 0.0;
        if (Schema::hasTable('event_expenses')) {
            $eventExpensesUsd += (float) DB::table('event_expenses')
                ->whereNull('deleted_at')
                ->sum(Schema::hasColumn('event_expenses', 'amount_base') ? 'amount_base' : 'amount_original');
        }
        if (Schema::hasTable('event_personal_expenses')) {
            $eventExpensesUsd += (float) DB::table('event_personal_expenses')
                ->whereNull('deleted_at')
                ->sum(Schema::hasColumn('event_personal_expenses', 'amount_base') ? 'amount_base' : 'amount_original');
        }

        $eventNetUsd = $eventIncomeUsd - $eventExpensesUsd;

        $payrollMonthUsd = 0.0;
        $payrollYearUsd = 0.0;
        if (Schema::hasTable('payroll_payments')) {
            $payrollMonthUsd = (float) DB::table('payroll_payments')
                ->whereNull('deleted_at')
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->sum('amount_usd');

            $payrollYearUsd = (float) DB::table('payroll_payments')
                ->whereNull('deleted_at')
                ->whereDate('payment_date', '>=', $yearStart)
                ->sum('amount_usd');
        }

        $royaltiesCurrentUsd = 0.0;
        $royaltyStatementsCurrent = 0;
        $royaltyUnmatchedLines = 0;
        if (Schema::hasTable('royalty_statements')) {
            $royaltyStatementsCurrent = (int) DB::table('royalty_statements')
                ->where('status', 'processed')
                ->where('is_current', true)
                ->whereNull('deleted_at')
                ->count();

            $royaltiesCurrentUsd = (float) DB::table('royalty_statements')
                ->where('status', 'processed')
                ->where('is_current', true)
                ->whereNull('deleted_at')
                ->sum('total_net_usd');
        }

        if (Schema::hasTable('royalty_statement_lines') && Schema::hasTable('royalty_statements')) {
            $royaltyUnmatchedLines = (int) DB::table('royalty_statement_lines as rsl')
                ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
                ->where('rs.status', 'processed')
                ->where('rs.is_current', true)
                ->whereNull('rs.deleted_at')
                ->whereNull('rsl.track_id')
                ->count();
        }

        $payoutPendingCount = 0;
        $payoutPendingUsd = 0.0;
        if (Schema::hasTable('royalty_payout_requests')) {
            $payoutPendingCountQuery = DB::table('royalty_payout_requests')
                ->whereIn('status', ['pending', 'approved']);

            $payoutPendingUsdQuery = DB::table('royalty_payout_requests')
                ->whereIn('status', ['pending', 'approved']);

            if (Schema::hasColumn('royalty_payout_requests', 'deleted_at')) {
                $payoutPendingCountQuery->whereNull('deleted_at');
                $payoutPendingUsdQuery->whereNull('deleted_at');
            }

            $payoutPendingCount = (int) $payoutPendingCountQuery->count();
            $payoutPendingUsd = (float) $payoutPendingUsdQuery->sum('requested_amount_usd');
        }

        $pendingExternalInvitations = 0;
        if (Schema::hasTable('external_artist_invitations')) {
            $pendingExternalInvitationsQuery = DB::table('external_artist_invitations')
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                });

            if (Schema::hasColumn('external_artist_invitations', 'deleted_at')) {
                $pendingExternalInvitationsQuery->whereNull('deleted_at');
            }

            $pendingExternalInvitations = (int) $pendingExternalInvitationsQuery->count();
        }

        $activeMasterSplits = Schema::hasTable('track_split_agreements')
            ? (int) DB::table('track_split_agreements')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count()
            : 0;

        $activeCompositionSplits = 0;
        if (Schema::hasTable('composition_split_sets')) {
            $activeCompositionSplits = (int) DB::table('composition_split_sets')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count();
        } elseif (Schema::hasTable('composition_split_agreements')) {
            $activeCompositionSplits = (int) DB::table('composition_split_agreements')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count();
        }

        return response()->json([
            'catalog' => [
                'artists' => (int) Artist::count(),
                'other_artists' => $otherArtistsCount,
                'releases' => (int) Release::count(),
                'tracks' => (int) Track::count(),
                'genres' => (int) Genre::count(),
                'compositions' => $compositionsCount,
            ],
            'operations' => [
                'events_total' => $eventsTotal,
                'events_upcoming' => $eventsUpcomingCount,
                'events_this_month' => $eventsThisMonthCount,
                'countries_count' => $distinctCountriesCount,
                'cities_count' => $distinctCitiesCount,
                'organizers' => $organizersCount,
                'workers' => $workersCount,
            ],
            'finance' => [
                'event_income_usd' => round($eventIncomeUsd, 2),
                'event_expenses_usd' => round($eventExpensesUsd, 2),
                'event_net_usd' => round($eventNetUsd, 2),
                'payroll_month_usd' => round($payrollMonthUsd, 2),
                'payroll_year_usd' => round($payrollYearUsd, 2),
                'royalties_current_usd' => round($royaltiesCurrentUsd, 2),
                'payout_pending_count' => $payoutPendingCount,
                'payout_pending_usd' => round($payoutPendingUsd, 2),
            ],
            'health' => [
                'royalty_statements_current' => $royaltyStatementsCurrent,
                'royalty_unmatched_lines' => $royaltyUnmatchedLines,
                'pending_external_invitations' => $pendingExternalInvitations,
                'active_master_splits' => $activeMasterSplits,
                'active_composition_splits' => $activeCompositionSplits,
            ],
            'top_countries' => $topCountries,
            'top_event_types' => $topEventTypes,
            'events' => $upcomingEvents,
        ]);
    }
}
