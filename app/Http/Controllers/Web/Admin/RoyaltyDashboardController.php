<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoyaltyPayoutRequest;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class RoyaltyDashboardController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $masterKpis = Cache::remember('royalties.dashboard.master_kpis', 600, function () {
            $base = DB::table('royalty_statement_lines as rsl')
                ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
                ->where('rs.status', 'processed')
                ->where('rs.is_current', true);

            $totals = (clone $base)
                ->selectRaw('COALESCE(SUM(rsl.net_total_usd),0) as total_usd, COALESCE(SUM(rsl.units),0) as total_units')
                ->first();

            $statementsCount = DB::table('royalty_statements')
                ->where('status', 'processed')
                ->where('is_current', true)
                ->count();

            $tracksWithRoyalties = (clone $base)
                ->whereNotNull('rsl.track_id')
                ->distinct('rsl.track_id')
                ->count('rsl.track_id');

            $unmatchedLines = (clone $base)
                ->whereNull('rsl.track_id')
                ->count();

            return [
                'total_usd' => (float) ($totals->total_usd ?? 0),
                'total_units' => (int) ($totals->total_units ?? 0),
                'statements_count' => $statementsCount,
                'tracks_with_royalties' => $tracksWithRoyalties,
                'unmatched_lines' => $unmatchedLines,
            ];
        });

        $masterTracksQuery = Track::query()
            ->select('tracks.id', 'tracks.title', 'tracks.isrc', 'tracks.release_id')
            ->leftJoin('royalty_statement_lines as rsl', 'tracks.id', '=', 'rsl.track_id')
            ->leftJoin('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->groupBy('tracks.id', 'tracks.title', 'tracks.isrc', 'tracks.release_id')
            ->selectRaw('COALESCE(SUM(rsl.net_total_usd),0) as total_usd, COALESCE(SUM(rsl.units),0) as total_units')
            ->orderByDesc('total_usd');

        if ($search !== '') {
            $masterTracksQuery->where(function ($query) use ($search) {
                $query->where('tracks.title', 'like', "%{$search}%")
                    ->orWhere('tracks.isrc', 'like', "%{$search}%");
            });
        }

        $masterTracks = $masterTracksQuery
            ->with(['release:id,title'])
            ->paginate(50)
            ->withQueryString();

        $masterMonthly = DB::table('royalty_statement_lines as rsl')
            ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->whereNotNull('rs.reporting_month_date')
            ->groupBy('rs.reporting_month_date')
            ->orderBy('rs.reporting_month_date')
            ->selectRaw('rs.reporting_month_date, COALESCE(SUM(rsl.net_total_usd),0) as total_usd, COALESCE(SUM(rsl.units),0) as total_units')
            ->get();

        $masterParticipants = collect();
        $masterBreakdownBySource = collect();
        $masterBreakdownByPeriod = collect();
        $masterBreakdownByTerritory = collect();

        if (Schema::hasTable('royalty_allocations')) {
            $masterAllocationsBase = DB::table('royalty_allocations as ra')
                ->join('royalty_statements as rs', 'rs.id', '=', 'ra.royalty_statement_id')
                ->leftJoin('users as u', 'u.id', '=', 'ra.party_user_id')
                ->leftJoin('artists as a', 'a.id', '=', 'ra.party_artist_id')
                ->where('rs.status', 'processed')
                ->where('rs.is_current', true);

            if (Schema::hasColumn('royalty_statements', 'is_reference_only')) {
                $masterAllocationsBase->where('rs.is_reference_only', false);
            }

            if (Schema::hasColumn('royalty_allocations', 'right_type')) {
                $masterAllocationsBase->where('ra.right_type', 'master');
            }

            $masterParticipants = (clone $masterAllocationsBase)
                ->groupBy('ra.party_user_id', 'ra.party_artist_id', 'ra.party_email', 'u.name', 'u.stage_name', 'a.name')
                ->orderByDesc(DB::raw('COALESCE(SUM(ra.allocated_amount_usd),0)'))
                ->limit(20)
                ->selectRaw("
                    ra.party_user_id,
                    ra.party_artist_id,
                    ra.party_email,
                    COALESCE(NULLIF(u.stage_name, ''), NULLIF(a.name, ''), NULLIF(u.name, ''), ra.party_email, 'Sin identificar') as participant_name,
                    COALESCE(SUM(ra.allocated_amount_usd),0) as total_usd,
                    COALESCE(SUM(CASE WHEN ra.status = 'accrued' THEN ra.allocated_amount_usd ELSE 0 END),0) as accrued_usd,
                    COALESCE(SUM(CASE WHEN ra.status IN ('approved','payable') THEN ra.allocated_amount_usd ELSE 0 END),0) as payable_usd,
                    COALESCE(SUM(CASE WHEN ra.status = 'paid' THEN ra.allocated_amount_usd ELSE 0 END),0) as paid_usd
                ")
                ->get();

            $masterLineBase = DB::table('royalty_statement_lines as rsl')
                ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
                ->where('rs.status', 'processed')
                ->where('rs.is_current', true);

            if (Schema::hasColumn('royalty_statements', 'is_reference_only')) {
                $masterLineBase->where('rs.is_reference_only', false);
            }

            $masterBreakdownBySource = (clone $masterLineBase)
                ->groupBy('rs.provider')
                ->orderByDesc(DB::raw('COALESCE(SUM(rsl.net_total_usd),0)'))
                ->selectRaw('COALESCE(rs.provider, "unknown") as label, COALESCE(SUM(rsl.net_total_usd),0) as total_usd, COALESCE(SUM(rsl.units),0) as total_units')
                ->get();

            $masterBreakdownByPeriod = (clone $masterLineBase)
                ->groupBy('rs.reporting_period', 'rs.reporting_month_date')
                ->orderByDesc('rs.reporting_month_date')
                ->limit(24)
                ->selectRaw('COALESCE(rs.reporting_period, DATE_FORMAT(rs.reporting_month_date, "%b-%y")) as label, COALESCE(SUM(rsl.net_total_usd),0) as total_usd, COALESCE(SUM(rsl.units),0) as total_units')
                ->get();

            $masterBreakdownByTerritory = (clone $masterLineBase)
                ->groupBy('rsl.country')
                ->orderByDesc(DB::raw('COALESCE(SUM(rsl.net_total_usd),0)'))
                ->limit(20)
                ->selectRaw('COALESCE(NULLIF(rsl.country, ""), "N/A") as label, COALESCE(SUM(rsl.net_total_usd),0) as total_usd, COALESCE(SUM(rsl.units),0) as total_units')
                ->get();
        }

        $compositionKpis = [
            'total_usd' => 0.0,
            'total_units' => 0,
            'statements_count' => 0,
            'compositions_with_royalties' => 0,
            'unmatched_lines' => 0,
        ];
        $compositionMonthly = collect();
        $compositionRows = collect();
        $compositionParticipants = collect();
        $compositionBreakdownBySource = collect();
        $compositionBreakdownByPeriod = collect();
        $compositionBreakdownByTerritory = collect();

        if (Schema::hasTable('composition_royalty_statements') && Schema::hasTable('composition_royalty_lines')) {
            $compositionBase = DB::table('composition_royalty_lines as crl')
                ->join('composition_royalty_statements as crs', 'crs.id', '=', 'crl.composition_royalty_statement_id')
                ->where('crs.status', 'processed')
                ->where('crs.is_current', true);

            $compositionTotals = (clone $compositionBase)
                ->selectRaw('COALESCE(SUM(crl.amount_usd),0) as total_usd, COALESCE(SUM(crl.units),0) as total_units')
                ->first();

            $compositionStatementsCount = DB::table('composition_royalty_statements')
                ->where('status', 'processed')
                ->where('is_current', true)
                ->count();

            $compositionsWithRoyalties = (clone $compositionBase)
                ->whereNotNull('crl.composition_id')
                ->distinct('crl.composition_id')
                ->count('crl.composition_id');

            $unmatchedCompositionLines = (clone $compositionBase)
                ->where(function ($query) {
                    $query->whereNull('crl.composition_id');

                    if (Schema::hasColumn('composition_royalty_lines', 'match_status')) {
                        $query->orWhere('crl.match_status', '!=', 'matched');
                    }
                })
                ->count();

            $compositionKpis = [
                'total_usd' => (float) ($compositionTotals->total_usd ?? 0),
                'total_units' => (int) ($compositionTotals->total_units ?? 0),
                'statements_count' => (int) $compositionStatementsCount,
                'compositions_with_royalties' => (int) $compositionsWithRoyalties,
                'unmatched_lines' => (int) $unmatchedCompositionLines,
            ];

            $compositionMonthly = DB::table('composition_royalty_lines as crl')
                ->join('composition_royalty_statements as crs', 'crs.id', '=', 'crl.composition_royalty_statement_id')
                ->where('crs.status', 'processed')
                ->where('crs.is_current', true)
                ->whereNotNull('crs.reporting_month_date')
                ->groupBy('crs.reporting_month_date')
                ->orderBy('crs.reporting_month_date')
                ->selectRaw('crs.reporting_month_date, COALESCE(SUM(crl.amount_usd),0) as total_usd, COALESCE(SUM(crl.units),0) as total_units')
                ->get();

            if (Schema::hasTable('compositions')) {
                $compositionRows = DB::table('compositions as c')
                    ->join('composition_royalty_lines as crl', 'c.id', '=', 'crl.composition_id')
                    ->join('composition_royalty_statements as crs', 'crs.id', '=', 'crl.composition_royalty_statement_id')
                    ->where('crs.status', 'processed')
                    ->where('crs.is_current', true)
                    ->groupBy('c.id', 'c.title', 'c.iswc')
                    ->selectRaw('c.id, c.title, c.iswc, COALESCE(SUM(crl.amount_usd),0) as total_usd, COALESCE(SUM(crl.units),0) as total_units')
                    ->orderByDesc('total_usd')
                    ->limit(20)
                    ->get();
            }

            if (Schema::hasTable('composition_allocations')) {
                $compositionAllocBase = DB::table('composition_allocations as ca')
                    ->join('composition_royalty_statements as crs', 'crs.id', '=', 'ca.composition_royalty_statement_id')
                    ->leftJoin('users as u', 'u.id', '=', 'ca.party_user_id')
                    ->leftJoin('artists as a', 'a.id', '=', 'ca.party_artist_id')
                    ->where('crs.status', 'processed')
                    ->where('crs.is_current', true);

                $compositionParticipants = (clone $compositionAllocBase)
                    ->groupBy('ca.party_user_id', 'ca.party_artist_id', 'ca.party_email', 'u.name', 'u.stage_name', 'a.name')
                    ->orderByDesc(DB::raw('COALESCE(SUM(ca.allocated_amount_usd),0)'))
                    ->limit(20)
                    ->selectRaw("
                        ca.party_user_id,
                        ca.party_artist_id,
                        ca.party_email,
                        COALESCE(NULLIF(u.stage_name, ''), NULLIF(a.name, ''), NULLIF(u.name, ''), ca.party_email, 'Sin identificar') as participant_name,
                        COALESCE(SUM(ca.allocated_amount_usd),0) as total_usd,
                        COALESCE(SUM(CASE WHEN ca.status = 'accrued' THEN ca.allocated_amount_usd ELSE 0 END),0) as accrued_usd,
                        COALESCE(SUM(CASE WHEN ca.status IN ('approved','payable') THEN ca.allocated_amount_usd ELSE 0 END),0) as payable_usd,
                        COALESCE(SUM(CASE WHEN ca.status = 'paid' THEN ca.allocated_amount_usd ELSE 0 END),0) as paid_usd
                    ")
                    ->get();
            }

            $compositionBreakdownBySource = DB::table('composition_royalty_lines as crl')
                ->join('composition_royalty_statements as crs', 'crs.id', '=', 'crl.composition_royalty_statement_id')
                ->where('crs.status', 'processed')
                ->where('crs.is_current', true)
                ->groupBy('crl.source_name')
                ->orderByDesc(DB::raw('COALESCE(SUM(crl.amount_usd),0)'))
                ->selectRaw('COALESCE(NULLIF(crl.source_name, ""), "N/A") as label, COALESCE(SUM(crl.amount_usd),0) as total_usd, COALESCE(SUM(crl.units),0) as total_units')
                ->get();

            $compositionBreakdownByPeriod = DB::table('composition_royalty_lines as crl')
                ->join('composition_royalty_statements as crs', 'crs.id', '=', 'crl.composition_royalty_statement_id')
                ->where('crs.status', 'processed')
                ->where('crs.is_current', true)
                ->groupBy('crs.reporting_period', 'crs.reporting_month_date')
                ->orderByDesc('crs.reporting_month_date')
                ->limit(24)
                ->selectRaw('COALESCE(crs.reporting_period, DATE_FORMAT(crs.reporting_month_date, "%b-%y")) as label, COALESCE(SUM(crl.amount_usd),0) as total_usd, COALESCE(SUM(crl.units),0) as total_units')
                ->get();

            $compositionBreakdownByTerritory = DB::table('composition_royalty_lines as crl')
                ->join('composition_royalty_statements as crs', 'crs.id', '=', 'crl.composition_royalty_statement_id')
                ->where('crs.status', 'processed')
                ->where('crs.is_current', true)
                ->groupBy('crl.territory_code')
                ->orderByDesc(DB::raw('COALESCE(SUM(crl.amount_usd),0)'))
                ->limit(20)
                ->selectRaw('COALESCE(NULLIF(crl.territory_code, ""), "N/A") as label, COALESCE(SUM(crl.amount_usd),0) as total_usd, COALESCE(SUM(crl.units),0) as total_units')
                ->get();
        }

        $payoutSummary = [
            'pending_count' => 0,
            'pending_amount_usd' => 0.0,
            'paid_amount_usd' => 0.0,
        ];
        $payoutRequests = collect();

        if (Schema::hasTable('royalty_payout_requests')) {
            $payoutSummary['pending_count'] = RoyaltyPayoutRequest::query()
                ->whereIn('status', ['pending', 'approved'])
                ->count();
            $payoutSummary['pending_amount_usd'] = (float) RoyaltyPayoutRequest::query()
                ->whereIn('status', ['pending', 'approved'])
                ->sum('requested_amount_usd');
            $payoutSummary['paid_amount_usd'] = (float) RoyaltyPayoutRequest::query()
                ->where('status', 'paid')
                ->sum('requested_amount_usd');

            $payoutRequests = RoyaltyPayoutRequest::query()
                ->whereIn('status', ['pending', 'approved'])
                ->orderByDesc('requested_at')
                ->limit(10)
                ->get([
                    'id',
                    'requester_name',
                    'requester_email',
                    'requested_amount_usd',
                    'status',
                    'requested_at',
                ]);
        }

        return Inertia::render('Admin/Royalties/Dashboard', [
            'master_kpis' => $masterKpis,
            'composition_kpis' => $compositionKpis,
            'master_tracks' => $masterTracks,
            'composition_rows' => $compositionRows,
            'master_monthly' => $masterMonthly,
            'composition_monthly' => $compositionMonthly,
            'master_participants' => $masterParticipants,
            'composition_participants' => $compositionParticipants,
            'master_breakdown' => [
                'source' => $masterBreakdownBySource,
                'period' => $masterBreakdownByPeriod,
                'territory' => $masterBreakdownByTerritory,
            ],
            'composition_breakdown' => [
                'source' => $compositionBreakdownBySource,
                'period' => $compositionBreakdownByPeriod,
                'territory' => $compositionBreakdownByTerritory,
            ],
            // Compatibilidad hacia atrás mientras se termina de migrar UI.
            'kpis' => $masterKpis,
            'tracks' => $masterTracks,
            'monthly' => $masterMonthly,
            'payout_summary' => $payoutSummary,
            'payout_requests' => $payoutRequests,
            'filters' => [
                'q' => $search,
            ],
        ]);
    }
}
