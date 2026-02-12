<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Models\RoyaltyStatement;
use App\Models\Track;
use App\Models\TrackSplitAgreement;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackController extends Controller
{
    public function index(Request $request)
    {
        $artist = $request->user()->artist()->firstOrFail();

        $summarySub = DB::table('royalty_statement_lines as rsl')
            ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->whereNotNull('rsl.track_id')
            ->groupBy('rsl.track_id')
            ->selectRaw('rsl.track_id, COALESCE(SUM(rsl.units),0) as total_units, COALESCE(SUM(rsl.net_total_usd),0) as total_royalties_usd');

        $tracks = Track::query()
            ->where(function ($query) use ($artist) {
                $query->whereHas('artists', fn($q) => $q->where('artists.id', $artist->id))
                    ->orWhereHas('splitAgreements', function ($q) use ($artist) {
                        $q->where('split_type', 'master')
                            ->where('status', 'active')
                            ->whereHas('participants', fn($p) => $p->where('artist_id', $artist->id));
                    });
            })
            ->leftJoinSub($summarySub, 'royalty_summary', function ($join) {
                $join->on('tracks.id', '=', 'royalty_summary.track_id');
            })
            ->select(
                'tracks.*',
                DB::raw('COALESCE(royalty_summary.total_units, 0) as total_units'),
                DB::raw('COALESCE(royalty_summary.total_royalties_usd, 0) as total_royalties_usd')
            )
            ->with(['release:id,title'])
            ->orderBy('tracks.title')
            ->paginate(10);

        return Inertia::render('Artist/Tracks/Index', [
            'tracks' => $tracks,
        ]);
    }

    public function edit(Request $request, Track $track)
    {
        $artist = $request->user()->artist()->firstOrFail();

        abort_unless(
            $track->artists()->where('artists.id', $artist->id)->exists(),
            403
        );

        return Inertia::render('Artist/Tracks/Edit', [
            'track' => $track->load('release:id,title'),
        ]);
    }

    public function update(Request $request, Track $track)
    {
        $artist = $request->user()->artist()->firstOrFail();

        abort_unless(
            $track->artists()->where('artists.id', $artist->id)->exists(),
            403
        );

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            // campos permitidos al artista
            'spotify_url' => ['nullable', 'url'],
            'youtube_url' => ['nullable', 'url'],
        ]);

        $track->update($data);

        return redirect()->route('artist.tracks.index')->with('success', 'Pista actualizada');
    }

    public function royalties(Request $request, Track $track)
    {
        $artist = $request->user()->artist()->firstOrFail();

        $this->authorizeTrackAccess($track, $artist->id);

        $agreement = TrackSplitAgreement::query()
            ->where('track_id', $track->id)
            ->where('split_type', 'master')
            ->where('status', 'active')
            ->with(['participants' => function ($query) use ($artist) {
                $query->where('artist_id', $artist->id);
            }])
            ->first();

        $participant = $agreement?->participants->first();
        $myPct = $participant?->percentage !== null ? (float) $participant->percentage : null;

        $cards = DB::table('royalty_statement_lines as rsl')
            ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rsl.track_id', $track->id)
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->groupBy('rs.id', 'rs.reporting_period', 'rs.reporting_month_date', 'rs.created_at')
            ->selectRaw('rs.id as statement_id, rs.reporting_period, rs.reporting_month_date, rs.created_at, MAX(rsl.activity_period_text) as activity_period_text, COALESCE(SUM(rsl.units),0) as units_total, COALESCE(SUM(rsl.net_total_usd),0) as total_track_usd')
            ->orderByRaw('rs.reporting_month_date IS NULL, rs.reporting_month_date DESC, rs.created_at DESC')
            ->get()
            ->map(function ($row) use ($myPct) {
                if (empty($row->reporting_period) && !empty($row->reporting_month_date)) {
                    $row->reporting_period = strtoupper(date('M-y', strtotime($row->reporting_month_date)));
                }
                if (empty($row->reporting_period) && !empty($row->activity_period_text)) {
                    $row->reporting_period = $this->formatPeriodFromText($row->activity_period_text);
                }
                if (empty($row->reporting_period) && !empty($row->created_at)) {
                    $row->reporting_period = strtoupper(date('M-y', strtotime($row->created_at)));
                }
                $total = (float) $row->total_track_usd;
                $row->my_pct = $myPct;
                $row->my_share_usd = $myPct === null ? null : round($total * ($myPct / 100), 6);
                return $row;
            });

        return Inertia::render('Artist/Tracks/Royalties/Show', [
            'track' => $track->load('release:id,title'),
            'cards' => $cards,
            'my_pct' => $myPct,
        ]);
    }

    public function royaltyDetail(Request $request, Track $track, RoyaltyStatement $statement)
    {
        $artist = $request->user()->artist()->firstOrFail();
        $this->authorizeTrackAccess($track, $artist->id);

        abort_unless($statement->status === 'processed' && $statement->is_current, 403);

        $agreement = TrackSplitAgreement::query()
            ->where('track_id', $track->id)
            ->where('split_type', 'master')
            ->where('status', 'active')
            ->with(['participants' => function ($query) use ($artist) {
                $query->where('artist_id', $artist->id);
            }])
            ->first();

        $participant = $agreement?->participants->first();
        $myPct = $participant?->percentage !== null ? (float) $participant->percentage : null;

        $summary = DB::table('royalty_statement_lines as rsl')
            ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rsl.track_id', $track->id)
            ->where('rs.id', $statement->id)
            ->selectRaw('COALESCE(SUM(rsl.units),0) as units_total, COALESCE(SUM(rsl.net_total_usd),0) as total_track_usd')
            ->first();

        $total = (float) ($summary->total_track_usd ?? 0);
        $summaryPayload = [
            'units_total' => (int) ($summary->units_total ?? 0),
            'total_track_usd' => $total,
            'my_pct' => $myPct,
            'my_share_usd' => $myPct === null ? null : round($total * ($myPct / 100), 6),
        ];

        $lines = DB::table('royalty_statement_lines as rsl')
            ->where('rsl.track_id', $track->id)
            ->where('rsl.royalty_statement_id', $statement->id)
            ->select('rsl.id', 'rsl.channel', 'rsl.country', 'rsl.activity_period_text', 'rsl.units', 'rsl.net_total_usd')
            ->orderByDesc('rsl.net_total_usd')
            ->paginate(50);

        $reportingPeriod = $statement->reporting_period;
        if (!$reportingPeriod && $statement->reporting_month_date) {
            $reportingPeriod = strtoupper(date('M-y', strtotime($statement->reporting_month_date)));
        }
        if (!$reportingPeriod && $statement->created_at) {
            $reportingPeriod = strtoupper(date('M-y', strtotime($statement->created_at)));
        }

        return Inertia::render('Artist/Tracks/Royalties/Detail', [
            'track' => $track->load('release:id,title'),
            'statement' => [
                'id' => $statement->id,
                'reporting_period' => $reportingPeriod,
                'reporting_month_date' => $statement->reporting_month_date,
            ],
            'summary' => $summaryPayload,
            'lines' => $lines,
        ]);
    }

    private function formatPeriodFromText(string $value): ?string
    {
        try {
            $date = Carbon::parse($value)->startOfMonth();
            return strtoupper($date->format('M-y'));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function authorizeTrackAccess(Track $track, int $artistId): void
    {
        $hasAccess = $track->artists()->where('artists.id', $artistId)->exists()
            || $track->splitAgreements()
                ->where('split_type', 'master')
                ->where('status', 'active')
                ->whereHas('participants', fn($q) => $q->where('artist_id', $artistId))
                ->exists();

        abort_unless($hasAccess, 403);
    }
}
