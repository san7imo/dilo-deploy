<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Models\RoyaltyStatement;
use App\Models\Track;
use App\Models\TrackSplitAgreement;
use App\Services\RoyaltyPayoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class TrackController extends Controller
{
    public function index(Request $request, RoyaltyPayoutService $royaltyPayoutService)
    {
        $access = $this->resolveAccessContext($request);

        $unitsSub = DB::table('royalty_statement_lines as rsl')
            ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->whereNotNull('rsl.track_id')
            ->groupBy('rsl.track_id')
            ->selectRaw('rsl.track_id, COALESCE(SUM(rsl.units),0) as total_units');

        $summarySub = DB::table('royalty_statement_lines as rsl')
            ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->whereNotNull('rsl.track_id')
            ->groupBy('rsl.track_id')
            ->selectRaw('rsl.track_id, COALESCE(SUM(rsl.net_total_usd),0) as total_royalties_usd');

        if (Schema::hasTable('royalty_allocations')) {
            $summarySub = DB::table('royalty_allocations as ra')
                ->join('royalty_statements as rs', 'rs.id', '=', 'ra.royalty_statement_id')
                ->where('rs.status', 'processed')
                ->where('rs.is_current', true)
                ->whereNotNull('ra.track_id');

            if (Schema::hasColumn('royalty_statements', 'is_reference_only')) {
                $summarySub->where('rs.is_reference_only', false);
            }

            $this->applyAllocationOwnershipFilter($summarySub, $access);

            $summarySub->groupBy('ra.track_id')
                ->selectRaw('ra.track_id, COALESCE(SUM(ra.allocated_amount_usd),0) as total_royalties_usd');
        }

        $tracks = Track::query()
            ->where(fn($query) => $this->applyTrackOwnershipFilter($query, $access))
            ->leftJoinSub($unitsSub, 'units_summary', function ($join) {
                $join->on('tracks.id', '=', 'units_summary.track_id');
            })
            ->leftJoinSub($summarySub, 'royalty_summary', function ($join) {
                $join->on('tracks.id', '=', 'royalty_summary.track_id');
            })
            ->select(
                'tracks.*',
                DB::raw('COALESCE(units_summary.total_units, 0) as total_units'),
                DB::raw('COALESCE(royalty_summary.total_royalties_usd, 0) as total_royalties_usd')
            )
            ->with(['release:id,title'])
            ->orderBy('tracks.title')
            ->paginate(10);

        $royaltyOverview = $royaltyPayoutService->buildArtistRoyaltyOverview($access);

        return Inertia::render('Artist/Tracks/Index', [
            'tracks' => $tracks,
            'royalty_overview' => $royaltyOverview,
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
        $access = $this->resolveAccessContext($request);

        $this->authorizeTrackAccess($track, $access);

        $lineRows = DB::table('royalty_statement_lines as rsl')
            ->join('royalty_statements as rs', 'rs.id', '=', 'rsl.royalty_statement_id')
            ->where('rsl.track_id', $track->id)
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->select(
                'rs.id as statement_id',
                'rs.reporting_period',
                'rs.reporting_month_date',
                'rs.created_at as statement_created_at',
                'rsl.activity_period_text',
                'rsl.activity_month_date',
                'rsl.units',
                'rsl.net_total_usd'
            )
            ->get();

        $agreements = $this->loadOwnedMasterAgreements($track, $access);
        $cards = $this->buildRoyaltyCards($lineRows, $agreements);
        $allocationShares = $this->buildAllocationSharesByStatement($track->id, $access);

        if ($allocationShares->isNotEmpty()) {
            $cards = $cards->map(function ($card) use ($allocationShares) {
                $share = $allocationShares->get((int) $card->statement_id);
                if (!$share) {
                    return $card;
                }

                $card->my_pct = $share['my_pct'];
                $card->my_pct_variable = $share['my_pct_variable'];
                $card->my_share_usd = $share['my_share_usd'];

                return $card;
            });
        }

        return Inertia::render('Artist/Tracks/Royalties/Show', [
            'track' => $track->load('release:id,title'),
            'cards' => $cards,
            'my_pct' => null,
        ]);
    }

    public function royaltyDetail(Request $request, Track $track, RoyaltyStatement $statement)
    {
        $access = $this->resolveAccessContext($request);
        $this->authorizeTrackAccess($track, $access);

        abort_unless($statement->status === 'processed' && $statement->is_current, 403);

        $summaryRows = DB::table('royalty_statement_lines as rsl')
            ->where('rsl.track_id', $track->id)
            ->where('rsl.royalty_statement_id', $statement->id)
            ->select('rsl.activity_month_date', 'rsl.units', 'rsl.net_total_usd')
            ->get();

        $shareSummary = $this->buildAllocationShareSummary(
            $track->id,
            $statement->id,
            $access
        );

        if (!$shareSummary['has_data']) {
            $agreements = $this->loadOwnedMasterAgreements($track, $access);
            $legacyShare = $this->calculateShareForRows(
                $summaryRows,
                $agreements,
                $statement->reporting_month_date?->toDateString(),
                $statement->reporting_period
            );

            $shareSummary = array_merge($legacyShare, [
                'has_data' => $legacyShare['my_share_usd'] !== null,
            ]);
        }

        $total = (float) $summaryRows->sum(fn($row) => (float) $row->net_total_usd);
        $unitsTotal = (int) $summaryRows->sum(fn($row) => (int) $row->units);

        $summaryPayload = [
            'units_total' => $unitsTotal,
            'total_track_usd' => $total,
            'my_pct' => $shareSummary['my_pct'],
            'my_pct_variable' => $shareSummary['my_pct_variable'],
            'my_share_usd' => $shareSummary['my_share_usd'],
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

    private function applyTrackOwnershipFilter($query, array $access): void
    {
        if ($access['type'] === 'internal') {
            $query->whereHas('artists', fn($q) => $q->where('artists.id', $access['artist_id']))
                ->orWhereHas('splitAgreements', function ($q) use ($access) {
                    $q->where('split_type', 'master')
                        ->whereHas('participants', fn($p) => $p->where('artist_id', $access['artist_id']));
                });

            return;
        }

        $query->whereHas('splitAgreements', function ($q) use ($access) {
            $q->where('split_type', 'master')
                ->whereHas('participants', function ($participantQuery) use ($access) {
                    $participantQuery->where(function ($ownershipQuery) use ($access) {
                        $ownershipQuery->where('user_id', $access['user_id']);

                        if (!empty($access['email'])) {
                            $ownershipQuery->orWhereRaw('LOWER(payee_email) = ?', [$access['email']]);
                        }
                    });
                });
        });
    }

    private function applyParticipantOwnershipFilter($query, array $access): void
    {
        if ($access['type'] === 'internal') {
            $query->where('artist_id', $access['artist_id']);
            return;
        }

        $query->where(function ($ownershipQuery) use ($access) {
            $ownershipQuery->where('user_id', $access['user_id']);

            if (!empty($access['email'])) {
                $ownershipQuery->orWhereRaw('LOWER(payee_email) = ?', [$access['email']]);
            }
        });
    }

    private function resolveAccessContext(Request $request): array
    {
        $user = $request->user();
        $artist = $user->artist()->first();

        if ($artist && $artist->artist_origin === 'internal') {
            return [
                'type' => 'internal',
                'artist_id' => $artist->id,
                'user_id' => $user->id,
                'email' => strtolower((string) $user->email),
            ];
        }

        if ($user->hasRole('external_artist')) {
            return [
                'type' => 'external',
                'artist_id' => null,
                'user_id' => $user->id,
                'email' => strtolower((string) $user->email),
            ];
        }

        abort(403);
    }

    private function authorizeTrackAccess(Track $track, array $access): void
    {
        if ($access['type'] === 'internal') {
            $hasAccess = $track->artists()->where('artists.id', $access['artist_id'])->exists()
                || $track->splitAgreements()
                    ->where('split_type', 'master')
                    ->whereHas('participants', fn($q) => $q->where('artist_id', $access['artist_id']))
                    ->exists();

            abort_unless($hasAccess, 403);
            return;
        }

        $hasAccess = $track->splitAgreements()
            ->where('split_type', 'master')
            ->whereHas('participants', function ($query) use ($access) {
                $query->where(function ($ownershipQuery) use ($access) {
                    $ownershipQuery->where('user_id', $access['user_id']);

                    if (!empty($access['email'])) {
                        $ownershipQuery->orWhereRaw('LOWER(payee_email) = ?', [$access['email']]);
                    }
                });
            })
            ->exists();

        abort_unless($hasAccess, 403);
    }

    private function buildAllocationSharesByStatement(int $trackId, array $access): Collection
    {
        if (!Schema::hasTable('royalty_allocations')) {
            return collect();
        }

        $query = DB::table('royalty_allocations as ra')
            ->join('royalty_statements as rs', 'rs.id', '=', 'ra.royalty_statement_id')
            ->where('ra.track_id', $trackId)
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true);

        if (Schema::hasColumn('royalty_allocations', 'right_type')) {
            $query->where('ra.right_type', 'master');
        }

        $this->applyAllocationOwnershipFilter($query, $access);

        $rows = $query
            ->groupBy('ra.royalty_statement_id')
            ->selectRaw('
                ra.royalty_statement_id as statement_id,
                COALESCE(SUM(ra.allocated_amount_usd),0) as my_share_usd,
                MIN(ra.split_percentage) as min_pct,
                MAX(ra.split_percentage) as max_pct
            ')
            ->get();

        return $rows->mapWithKeys(function ($row) {
            $minPct = (float) ($row->min_pct ?? 0);
            $maxPct = (float) ($row->max_pct ?? 0);
            $isVariable = abs($maxPct - $minPct) >= 0.0001;

            return [
                (int) $row->statement_id => [
                    'my_pct' => $isVariable ? null : $minPct,
                    'my_pct_variable' => $isVariable,
                    'my_share_usd' => (float) ($row->my_share_usd ?? 0),
                ],
            ];
        });
    }

    private function buildAllocationShareSummary(int $trackId, int $statementId, array $access): array
    {
        if (!Schema::hasTable('royalty_allocations')) {
            return [
                'my_pct' => null,
                'my_pct_variable' => false,
                'my_share_usd' => null,
                'has_data' => false,
            ];
        }

        $query = DB::table('royalty_allocations as ra')
            ->where('ra.track_id', $trackId)
            ->where('ra.royalty_statement_id', $statementId);

        if (Schema::hasColumn('royalty_allocations', 'right_type')) {
            $query->where('ra.right_type', 'master');
        }

        $this->applyAllocationOwnershipFilter($query, $access);

        $row = $query
            ->selectRaw('
                COALESCE(SUM(ra.allocated_amount_usd),0) as my_share_usd,
                MIN(ra.split_percentage) as min_pct,
                MAX(ra.split_percentage) as max_pct,
                COUNT(*) as allocations_count
            ')
            ->first();

        if (!$row || (int) ($row->allocations_count ?? 0) === 0) {
            return [
                'my_pct' => null,
                'my_pct_variable' => false,
                'my_share_usd' => null,
                'has_data' => false,
            ];
        }

        $minPct = (float) ($row->min_pct ?? 0);
        $maxPct = (float) ($row->max_pct ?? 0);
        $isVariable = abs($maxPct - $minPct) >= 0.0001;

        return [
            'my_pct' => $isVariable ? null : $minPct,
            'my_pct_variable' => $isVariable,
            'my_share_usd' => (float) ($row->my_share_usd ?? 0),
            'has_data' => true,
        ];
    }

    private function applyAllocationOwnershipFilter($query, array $access): void
    {
        $query->where(function ($ownershipQuery) use ($access) {
            if ($access['type'] === 'internal') {
                $ownershipQuery->where('ra.party_artist_id', $access['artist_id'])
                    ->orWhere('ra.party_user_id', $access['user_id']);

                if (!empty($access['email'])) {
                    $ownershipQuery->orWhereRaw('LOWER(ra.party_email) = ?', [$access['email']]);
                }

                return;
            }

            $ownershipQuery->where('ra.party_user_id', $access['user_id']);

            if (!empty($access['email'])) {
                $ownershipQuery->orWhereRaw('LOWER(ra.party_email) = ?', [$access['email']]);
            }
        });
    }

    private function buildRoyaltyCards(Collection $lineRows, Collection $agreements): Collection
    {
        if ($lineRows->isEmpty()) {
            return collect();
        }

        return $lineRows
            ->groupBy('statement_id')
            ->map(function (Collection $rows, $statementId) use ($agreements) {
                $first = $rows->first();
                $totalTrackUsd = (float) $rows->sum(fn($row) => (float) $row->net_total_usd);
                $unitsTotal = (int) $rows->sum(fn($row) => (int) $row->units);

                $shareSummary = $this->calculateShareForRows(
                    $rows,
                    $agreements,
                    $first->reporting_month_date ?? null,
                    $first->reporting_period ?? null
                );

                $reportingPeriod = $first->reporting_period;
                $activityPeriodFallback = $rows->pluck('activity_period_text')->filter()->first();

                if (empty($reportingPeriod) && !empty($first->reporting_month_date)) {
                    $reportingPeriod = strtoupper(date('M-y', strtotime($first->reporting_month_date)));
                }
                if (empty($reportingPeriod) && !empty($activityPeriodFallback)) {
                    $reportingPeriod = $this->formatPeriodFromText($activityPeriodFallback);
                }
                if (empty($reportingPeriod) && !empty($first->statement_created_at)) {
                    $reportingPeriod = strtoupper(date('M-y', strtotime($first->statement_created_at)));
                }

                return (object) [
                    'statement_id' => (int) $statementId,
                    'reporting_period' => $reportingPeriod,
                    'reporting_month_date' => $first->reporting_month_date,
                    'units_total' => $unitsTotal,
                    'total_track_usd' => $totalTrackUsd,
                    'my_pct' => $shareSummary['my_pct'],
                    'my_pct_variable' => $shareSummary['my_pct_variable'],
                    'my_share_usd' => $shareSummary['my_share_usd'],
                    'sort_date' => $first->reporting_month_date ?: $first->statement_created_at,
                ];
            })
            ->sortByDesc(function ($card) {
                return $card->sort_date ? strtotime((string) $card->sort_date) : 0;
            })
            ->map(function ($card) {
                unset($card->sort_date);
                return $card;
            })
            ->values();
    }

    private function loadOwnedMasterAgreements(Track $track, array $access): Collection
    {
        $agreements = TrackSplitAgreement::query()
            ->where('track_id', $track->id)
            ->where('split_type', 'master')
            ->with(['participants' => function ($query) use ($access) {
                $this->applyParticipantOwnershipFilter($query, $access);
            }])
            ->orderBy('created_at')
            ->get(['id', 'status', 'effective_from', 'effective_to', 'created_at']);

        return $agreements
            ->map(function (TrackSplitAgreement $agreement) {
                $percentage = (float) $agreement->participants->sum(fn($participant) => (float) $participant->percentage);
                if ($percentage <= 0) {
                    return null;
                }

                return [
                    'id' => (int) $agreement->id,
                    'status' => (string) $agreement->status,
                    'effective_from' => $agreement->effective_from ? Carbon::parse($agreement->effective_from)->startOfDay() : null,
                    'effective_to' => $agreement->effective_to ? Carbon::parse($agreement->effective_to)->startOfDay() : null,
                    'created_at' => $agreement->created_at ? Carbon::parse($agreement->created_at) : null,
                    'percentage' => $percentage,
                ];
            })
            ->filter()
            ->values();
    }

    private function calculateShareForRows(
        Collection $rows,
        Collection $agreements,
        ?string $fallbackReportingMonthDate = null,
        ?string $fallbackReportingPeriod = null
    ): array {
        if ($rows->isEmpty() || $agreements->isEmpty()) {
            return [
                'my_pct' => null,
                'my_pct_variable' => false,
                'my_share_usd' => null,
            ];
        }

        $myShareUsd = 0.0;
        $hasShare = false;
        $percentages = [];

        foreach ($rows as $row) {
            $referenceDate = $this->resolveReferenceDate(
                $row->activity_month_date ?? null,
                $row->reporting_month_date ?? $fallbackReportingMonthDate,
                $row->reporting_period ?? $fallbackReportingPeriod
            );

            $pct = $this->resolvePercentageForDate($agreements, $referenceDate);
            if ($pct === null) {
                continue;
            }

            $hasShare = true;
            $net = (float) ($row->net_total_usd ?? 0);
            $myShareUsd += $net * ($pct / 100);
            $percentages[(string) round($pct, 6)] = $pct;
        }

        if (!$hasShare) {
            return [
                'my_pct' => null,
                'my_pct_variable' => false,
                'my_share_usd' => null,
            ];
        }

        $pctValues = array_values($percentages);
        $isVariable = count($pctValues) > 1;

        return [
            'my_pct' => $isVariable ? null : (float) $pctValues[0],
            'my_pct_variable' => $isVariable,
            'my_share_usd' => round($myShareUsd, 6),
        ];
    }

    private function resolvePercentageForDate(Collection $agreements, ?Carbon $referenceDate): ?float
    {
        if ($agreements->isEmpty()) {
            return null;
        }

        if ($referenceDate) {
            $candidates = $agreements->filter(function (array $agreement) use ($referenceDate) {
                $from = $agreement['effective_from'];
                $to = $agreement['effective_to'];

                $startsOk = !$from || $from->lte($referenceDate);
                $endsOk = !$to || $to->gte($referenceDate);

                return $startsOk && $endsOk;
            });

            if ($candidates->isNotEmpty()) {
                return (float) ($this->sortAgreementsByPriority($candidates)->first()['percentage'] ?? 0);
            }
        }

        $activeAgreement = $agreements->first(fn(array $agreement) => $agreement['status'] === 'active');
        if ($activeAgreement) {
            return (float) $activeAgreement['percentage'];
        }

        return (float) ($this->sortAgreementsByPriority($agreements)->first()['percentage'] ?? 0);
    }

    private function sortAgreementsByPriority(Collection $agreements): Collection
    {
        return $agreements->sort(function (array $a, array $b) {
            $fromA = $a['effective_from']?->timestamp ?? PHP_INT_MIN;
            $fromB = $b['effective_from']?->timestamp ?? PHP_INT_MIN;
            if ($fromA !== $fromB) {
                return $fromB <=> $fromA;
            }

            $createdA = $a['created_at']?->timestamp ?? 0;
            $createdB = $b['created_at']?->timestamp ?? 0;

            return $createdB <=> $createdA;
        })->values();
    }

    private function resolveReferenceDate(
        ?string $activityMonthDate,
        ?string $reportingMonthDate,
        ?string $reportingPeriod
    ): ?Carbon {
        if (!empty($activityMonthDate)) {
            try {
                return Carbon::parse($activityMonthDate)->startOfDay();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (!empty($reportingMonthDate)) {
            try {
                return Carbon::parse($reportingMonthDate)->startOfDay();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (!empty($reportingPeriod)) {
            return $this->parseReportingPeriod($reportingPeriod);
        }

        return null;
    }

    private function parseReportingPeriod(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?<mon>[A-Za-z]{3})[-\\s](?<year>\\d{2,4})$/', $value, $matches)) {
            $month = ucfirst(strtolower($matches['mon']));
            $year = $matches['year'];
            if (strlen($year) === 2) {
                $year = '20' . $year;
            }

            try {
                return Carbon::parse($month . ' ' . $year)->startOfMonth();
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (preg_match('/^(?<year>\\d{4})[-\\/](?<month>\\d{1,2})$/', $value, $matches)) {
            try {
                return Carbon::createFromDate((int) $matches['year'], (int) $matches['month'], 1)->startOfMonth();
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->startOfMonth();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
