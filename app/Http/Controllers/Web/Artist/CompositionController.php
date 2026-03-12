<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Models\Composition;
use App\Models\RoyaltyStatement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class CompositionController extends Controller
{
    public function index(Request $request)
    {
        $access = $this->resolveAccessContext($request);
        $ownedIds = $this->resolveOwnedCompositionIds($access);

        $summarySub = null;
        if ($this->hasCompositionAllocationsTable()) {
            $summaryQuery = DB::table('royalty_allocations as ra')
                ->join('royalty_statements as rs', 'rs.id', '=', 'ra.royalty_statement_id')
                ->where('ra.right_type', 'composition')
                ->whereNotNull('ra.composition_id')
                ->where('rs.status', 'processed')
                ->where('rs.is_current', true)
                ->where('rs.is_reference_only', false);

            $this->applyAllocationOwnershipFilter($summaryQuery, $access, 'ra');

            $summarySub = $summaryQuery
                ->groupBy('ra.composition_id')
                ->selectRaw('ra.composition_id, COALESCE(SUM(ra.allocated_amount_usd),0) as total_my_share_usd, COUNT(DISTINCT ra.royalty_statement_id) as statements_count');
        }

        $query = Composition::query()
            ->whereIn('compositions.id', $ownedIds->all())
            ->withCount('tracks')
            ->orderBy('compositions.title');

        if ($summarySub) {
            $query->leftJoinSub($summarySub, 'royalty_summary', function ($join) {
                $join->on('compositions.id', '=', 'royalty_summary.composition_id');
            })->select(
                'compositions.*',
                DB::raw('COALESCE(royalty_summary.total_my_share_usd, 0) as total_my_share_usd'),
                DB::raw('COALESCE(royalty_summary.statements_count, 0) as statements_count')
            );
        }

        $compositions = $query->paginate(10);

        return Inertia::render('Artist/Compositions/Index', [
            'compositions' => $compositions,
        ]);
    }

    public function royalties(Request $request, Composition $composition)
    {
        $access = $this->resolveAccessContext($request);
        $this->authorizeCompositionAccess($composition, $access);

        $cards = $this->buildRoyaltyCards($composition->id, $access);

        return Inertia::render('Artist/Compositions/Royalties/Show', [
            'composition' => $composition->load('tracks:id,title,isrc'),
            'cards' => $cards,
        ]);
    }

    public function royaltyDetail(Request $request, Composition $composition, RoyaltyStatement $statement)
    {
        $access = $this->resolveAccessContext($request);
        $this->authorizeCompositionAccess($composition, $access);

        abort_unless($statement->status === 'processed' && $statement->is_current && !$statement->is_reference_only, 403);

        $summary = $this->buildDetailSummary($composition->id, $statement->id, $access);
        $lines = $this->buildDetailLines($composition->id, $statement->id, $access);

        $reportingPeriod = $statement->reporting_period;
        if (!$reportingPeriod && $statement->reporting_month_date) {
            $reportingPeriod = strtoupper(date('M-y', strtotime($statement->reporting_month_date)));
        }
        if (!$reportingPeriod && $statement->created_at) {
            $reportingPeriod = strtoupper(date('M-y', strtotime($statement->created_at)));
        }

        return Inertia::render('Artist/Compositions/Royalties/Detail', [
            'composition' => $composition->load('tracks:id,title,isrc'),
            'statement' => [
                'id' => $statement->id,
                'reporting_period' => $reportingPeriod,
                'reporting_month_date' => $statement->reporting_month_date,
            ],
            'summary' => $summary,
            'lines' => $lines,
        ]);
    }

    private function resolveAccessContext(Request $request): array
    {
        $user = $request->user();
        $artist = $user->artist()->first();

        if ($artist) {
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

    private function resolveOwnedCompositionIds(array $access): Collection
    {
        $ids = collect();

        if (
            Schema::hasTable('composition_split_participants')
            && Schema::hasTable('composition_split_sets')
            && Schema::hasColumn('composition_split_participants', 'composition_split_set_id')
        ) {
            $splitQuery = DB::table('composition_split_participants as csp')
                ->join('composition_split_sets as css', 'css.id', '=', 'csp.composition_split_set_id')
                ->whereNull('csp.deleted_at')
                ->whereNull('css.deleted_at')
                ->where(function ($query) use ($access) {
                    if ($access['type'] === 'internal') {
                        $query->where('csp.artist_id', $access['artist_id'])
                            ->orWhere('csp.user_id', $access['user_id']);

                        if (!empty($access['email'])) {
                            $query->orWhereRaw('LOWER(csp.payee_email) = ?', [$access['email']]);
                        }

                        return;
                    }

                    $query->where('csp.user_id', $access['user_id']);
                    if (!empty($access['email'])) {
                        $query->orWhereRaw('LOWER(csp.payee_email) = ?', [$access['email']]);
                    }
                })
                ->pluck('css.composition_id');

            $ids = $ids->merge($splitQuery);
        } elseif (Schema::hasTable('composition_split_participants') && Schema::hasTable('composition_split_agreements')) {
            $splitQuery = DB::table('composition_split_participants as csp')
                ->join('composition_split_agreements as csa', 'csa.id', '=', 'csp.composition_split_agreement_id')
                ->whereNull('csp.deleted_at')
                ->whereNull('csa.deleted_at')
                ->where(function ($query) use ($access) {
                    if ($access['type'] === 'internal') {
                        $query->where('csp.artist_id', $access['artist_id'])
                            ->orWhere('csp.user_id', $access['user_id']);

                        if (!empty($access['email'])) {
                            $query->orWhereRaw('LOWER(csp.payee_email) = ?', [$access['email']]);
                        }

                        return;
                    }

                    $query->where('csp.user_id', $access['user_id']);
                    if (!empty($access['email'])) {
                        $query->orWhereRaw('LOWER(csp.payee_email) = ?', [$access['email']]);
                    }
                })
                ->pluck('csa.composition_id');

            $ids = $ids->merge($splitQuery);
        }

        if ($this->hasCompositionAllocationsTable()) {
            $allocationQuery = DB::table('royalty_allocations as ra')
                ->where('ra.right_type', 'composition')
                ->whereNotNull('ra.composition_id');

            $this->applyAllocationOwnershipFilter($allocationQuery, $access, 'ra');

            $ids = $ids->merge($allocationQuery->pluck('ra.composition_id'));
        }

        return $ids
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function authorizeCompositionAccess(Composition $composition, array $access): void
    {
        $ownedIds = $this->resolveOwnedCompositionIds($access);
        abort_unless($ownedIds->contains((int) $composition->id), 403);
    }

    private function buildRoyaltyCards(int $compositionId, array $access): Collection
    {
        if (!$this->hasCompositionAllocationsTable()) {
            return collect();
        }

        $mySharesQuery = DB::table('royalty_allocations as ra')
            ->join('royalty_statements as rs', 'rs.id', '=', 'ra.royalty_statement_id')
            ->where('ra.right_type', 'composition')
            ->where('ra.composition_id', $compositionId)
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true)
            ->where('rs.is_reference_only', false);

        $this->applyAllocationOwnershipFilter($mySharesQuery, $access, 'ra');

        $myShares = $mySharesQuery
            ->groupBy('ra.royalty_statement_id', 'rs.reporting_period', 'rs.reporting_month_date', 'rs.created_at')
            ->selectRaw('ra.royalty_statement_id as statement_id, rs.reporting_period, rs.reporting_month_date, rs.created_at as statement_created_at, COALESCE(SUM(ra.allocated_amount_usd),0) as my_share_usd, MIN(ra.split_percentage) as min_pct, MAX(ra.split_percentage) as max_pct')
            ->get();

        if ($myShares->isEmpty()) {
            return collect();
        }

        $statementIds = $myShares->pluck('statement_id')->map(fn($id) => (int) $id)->unique()->values();
        $totalsByStatement = $this->buildStatementTotals($compositionId, $statementIds);

        return $myShares
            ->map(function ($row) use ($totalsByStatement) {
                $statementId = (int) $row->statement_id;
                $totals = $totalsByStatement->get($statementId, [
                    'total_composition_usd' => 0.0,
                    'units_total' => 0,
                ]);

                $minPct = (float) ($row->min_pct ?? 0);
                $maxPct = (float) ($row->max_pct ?? 0);
                $isVariable = abs($maxPct - $minPct) >= 0.0001;

                return (object) [
                    'statement_id' => $statementId,
                    'reporting_period' => $row->reporting_period,
                    'reporting_month_date' => $row->reporting_month_date,
                    'units_total' => (int) ($totals['units_total'] ?? 0),
                    'total_composition_usd' => (float) ($totals['total_composition_usd'] ?? 0),
                    'my_pct' => $isVariable ? null : $minPct,
                    'my_pct_variable' => $isVariable,
                    'my_share_usd' => (float) ($row->my_share_usd ?? 0),
                    'sort_date' => $row->reporting_month_date ?: $row->statement_created_at,
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

    private function buildStatementTotals(int $compositionId, Collection $statementIds): Collection
    {
        if ($statementIds->isEmpty()) {
            return collect();
        }

        $lineTotalsSub = DB::table('royalty_allocations as ra')
            ->where('ra.right_type', 'composition')
            ->where('ra.composition_id', $compositionId)
            ->whereIn('ra.royalty_statement_id', $statementIds)
            ->groupBy('ra.royalty_statement_id', 'ra.royalty_statement_line_id')
            ->selectRaw('ra.royalty_statement_id, ra.royalty_statement_line_id, MAX(ra.gross_amount_usd) as line_gross');

        return DB::query()
            ->fromSub($lineTotalsSub, 'line_totals')
            ->join('royalty_statement_lines as rsl', 'rsl.id', '=', 'line_totals.royalty_statement_line_id')
            ->groupBy('line_totals.royalty_statement_id')
            ->selectRaw('line_totals.royalty_statement_id as statement_id, COALESCE(SUM(line_totals.line_gross),0) as total_composition_usd, COALESCE(SUM(rsl.units),0) as units_total')
            ->get()
            ->mapWithKeys(fn($row) => [
                (int) $row->statement_id => [
                    'total_composition_usd' => (float) ($row->total_composition_usd ?? 0),
                    'units_total' => (int) ($row->units_total ?? 0),
                ],
            ]);
    }

    private function buildDetailSummary(int $compositionId, int $statementId, array $access): array
    {
        $totals = $this->buildStatementTotals($compositionId, collect([$statementId]))
            ->get($statementId, [
                'total_composition_usd' => 0.0,
                'units_total' => 0,
            ]);

        if (!$this->hasCompositionAllocationsTable()) {
            return [
                'units_total' => (int) ($totals['units_total'] ?? 0),
                'total_composition_usd' => (float) ($totals['total_composition_usd'] ?? 0),
                'my_pct' => null,
                'my_pct_variable' => false,
                'my_share_usd' => null,
            ];
        }

        $myShareQuery = DB::table('royalty_allocations as ra')
            ->where('ra.right_type', 'composition')
            ->where('ra.composition_id', $compositionId)
            ->where('ra.royalty_statement_id', $statementId);

        $this->applyAllocationOwnershipFilter($myShareQuery, $access, 'ra');

        $myShareRow = $myShareQuery
            ->selectRaw('COALESCE(SUM(ra.allocated_amount_usd),0) as my_share_usd, MIN(ra.split_percentage) as min_pct, MAX(ra.split_percentage) as max_pct, COUNT(*) as allocations_count')
            ->first();

        if (!$myShareRow || (int) ($myShareRow->allocations_count ?? 0) === 0) {
            return [
                'units_total' => (int) ($totals['units_total'] ?? 0),
                'total_composition_usd' => (float) ($totals['total_composition_usd'] ?? 0),
                'my_pct' => null,
                'my_pct_variable' => false,
                'my_share_usd' => null,
            ];
        }

        $minPct = (float) ($myShareRow->min_pct ?? 0);
        $maxPct = (float) ($myShareRow->max_pct ?? 0);
        $isVariable = abs($maxPct - $minPct) >= 0.0001;

        return [
            'units_total' => (int) ($totals['units_total'] ?? 0),
            'total_composition_usd' => (float) ($totals['total_composition_usd'] ?? 0),
            'my_pct' => $isVariable ? null : $minPct,
            'my_pct_variable' => $isVariable,
            'my_share_usd' => (float) ($myShareRow->my_share_usd ?? 0),
        ];
    }

    private function buildDetailLines(int $compositionId, int $statementId, array $access)
    {
        $mySharesByLine = DB::table('royalty_allocations as ra')
            ->where('ra.right_type', 'composition')
            ->where('ra.composition_id', $compositionId)
            ->where('ra.royalty_statement_id', $statementId);

        $this->applyAllocationOwnershipFilter($mySharesByLine, $access, 'ra');

        $mySharesByLine = $mySharesByLine
            ->groupBy('ra.royalty_statement_line_id')
            ->selectRaw('ra.royalty_statement_line_id, COALESCE(SUM(ra.allocated_amount_usd),0) as my_share_usd');

        return DB::table('royalty_allocations as ra')
            ->join('royalty_statement_lines as rsl', 'rsl.id', '=', 'ra.royalty_statement_line_id')
            ->leftJoinSub($mySharesByLine, 'my_line_share', function ($join) {
                $join->on('my_line_share.royalty_statement_line_id', '=', 'ra.royalty_statement_line_id');
            })
            ->where('ra.right_type', 'composition')
            ->where('ra.composition_id', $compositionId)
            ->where('ra.royalty_statement_id', $statementId)
            ->groupBy(
                'ra.royalty_statement_line_id',
                'rsl.channel',
                'rsl.country',
                'rsl.activity_period_text',
                'my_line_share.my_share_usd'
            )
            ->selectRaw('ra.royalty_statement_line_id as id, rsl.channel, rsl.country, rsl.activity_period_text, MAX(rsl.units) as units, MAX(ra.gross_amount_usd) as gross_line_usd, COALESCE(my_line_share.my_share_usd, 0) as my_share_usd')
            ->orderByDesc('gross_line_usd')
            ->paginate(50);
    }

    private function hasCompositionAllocationsTable(): bool
    {
        return Schema::hasTable('royalty_allocations')
            && Schema::hasColumn('royalty_allocations', 'right_type')
            && Schema::hasColumn('royalty_allocations', 'composition_id');
    }

    private function applyAllocationOwnershipFilter($query, array $access, string $alias = 'ra'): void
    {
        $query->where(function ($ownershipQuery) use ($access, $alias) {
            if ($access['type'] === 'internal') {
                $ownershipQuery->where("{$alias}.party_artist_id", $access['artist_id'])
                    ->orWhere("{$alias}.party_user_id", $access['user_id']);

                if (!empty($access['email'])) {
                    $ownershipQuery->orWhereRaw("LOWER({$alias}.party_email) = ?", [$access['email']]);
                }

                return;
            }

            $ownershipQuery->where("{$alias}.party_user_id", $access['user_id']);

            if (!empty($access['email'])) {
                $ownershipQuery->orWhereRaw("LOWER({$alias}.party_email) = ?", [$access['email']]);
            }
        });
    }
}
