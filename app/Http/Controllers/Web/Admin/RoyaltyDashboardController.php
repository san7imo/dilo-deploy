<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RoyaltyDashboardController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $kpis = Cache::remember('royalties.dashboard.kpis', 600, function () {
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

        $tracksQuery = Track::query()
            ->select('tracks.id', 'tracks.title', 'tracks.isrc', 'tracks.release_id')
            ->leftJoin('royalty_statement_lines as rsl', 'tracks.id', '=', 'rsl.track_id')
            ->leftJoin('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->groupBy('tracks.id', 'tracks.title', 'tracks.isrc', 'tracks.release_id')
            ->selectRaw('COALESCE(SUM(rsl.net_total_usd),0) as total_usd, COALESCE(SUM(rsl.units),0) as total_units')
            ->orderByDesc('total_usd');

        if ($search !== '') {
            $tracksQuery->where(function ($query) use ($search) {
                $query->where('tracks.title', 'like', "%{$search}%")
                    ->orWhere('tracks.isrc', 'like', "%{$search}%");
            });
        }

        $tracks = $tracksQuery
            ->with(['release:id,title'])
            ->paginate(50)
            ->withQueryString();

        $monthly = DB::table('royalty_statement_lines as rsl')
            ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->whereNotNull('rs.reporting_month_date')
            ->groupBy('rs.reporting_month_date')
            ->orderBy('rs.reporting_month_date')
            ->selectRaw('rs.reporting_month_date, COALESCE(SUM(rsl.net_total_usd),0) as total_usd, COALESCE(SUM(rsl.units),0) as total_units')
            ->get();

        return Inertia::render('Admin/Royalties/Dashboard', [
            'kpis' => $kpis,
            'tracks' => $tracks,
            'monthly' => $monthly,
            'filters' => [
                'q' => $search,
            ],
        ]);
    }
}
