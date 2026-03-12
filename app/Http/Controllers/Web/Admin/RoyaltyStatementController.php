<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoyaltyStatementRequest;
use App\Http\Requests\UpdateRoyaltyStatementLineMatchRequest;
use App\Jobs\ProcessRoyaltyStatementJob;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\Track;
use App\Services\RoyaltyAllocationService;
use App\Services\Royalties\MasterRoyaltyDedupeService;
use App\Services\Royalties\MasterRoyaltyLineMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RoyaltyStatementController extends Controller
{
    public function index(Request $request)
    {
        $statements = RoyaltyStatement::with([
                'creator:id,name',
                'duplicateOf:id,original_filename,reporting_period,version',
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Admin/Royalties/Statements/Index', [
            'statements' => $statements,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Royalties/Statements/Create', [
            'providers' => [
                ['value' => 'symphonic', 'label' => 'Symphonic'],
                ['value' => 'sonosuite', 'label' => 'Sonosuite'],
            ],
        ]);
    }

    public function show(
        Request $request,
        RoyaltyStatement $statement,
        MasterRoyaltyLineMatcher $lineMatcher
    )
    {
        $statement->load([
            'creator:id,name',
            'duplicateOf:id,original_filename,reporting_period,version',
        ]);

        $matchFilter = $this->normalizeMatchFilter(
            $statement->status === 'processing' ? 'all' : $request->query('match')
        );

        $baseLinesQuery = RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id);

        $linesQuery = clone $baseLinesQuery;
        if ($matchFilter !== 'all') {
            if ($matchFilter === 'review') {
                $linesQuery->whereIn('match_status', [
                    RoyaltyStatementLine::MATCH_STATUS_UNMATCHED,
                    RoyaltyStatementLine::MATCH_STATUS_AMBIGUOUS,
                    RoyaltyStatementLine::MATCH_STATUS_DUPLICATE,
                ]);
            } else {
                $linesQuery->where('match_status', $matchFilter);
            }
        }

        $lines = (clone $linesQuery)
            ->select([
                'id',
                'royalty_statement_id',
                'track_id',
                'match_status',
                'match_meta',
                'track_title',
                'isrc',
                'upc',
                'channel',
                'country',
                'activity_period_text',
                'units',
                'net_total_usd',
            ])
            ->with('track:id,title')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        $linesCount = (clone $baseLinesQuery)->count();
        $statusCounts = (clone $baseLinesQuery)
            ->selectRaw('COALESCE(match_status, ?) as match_status, COUNT(*) as total', [RoyaltyStatementLine::MATCH_STATUS_UNMATCHED])
            ->groupBy('match_status')
            ->pluck('total', 'match_status');

        $matchedCount = (int) ($statusCounts[RoyaltyStatementLine::MATCH_STATUS_MATCHED] ?? 0);
        $unmatchedCount = (int) ($statusCounts[RoyaltyStatementLine::MATCH_STATUS_UNMATCHED] ?? 0);
        $ambiguousCount = (int) ($statusCounts[RoyaltyStatementLine::MATCH_STATUS_AMBIGUOUS] ?? 0);
        $duplicateCount = (int) ($statusCounts[RoyaltyStatementLine::MATCH_STATUS_DUPLICATE] ?? 0);
        $referenceOnlyCount = (int) ($statusCounts[RoyaltyStatementLine::MATCH_STATUS_REFERENCE_ONLY] ?? 0);

        $lineMatchOptions = $statement->status === 'processing'
            ? []
            : $this->buildLineMatchOptions($lines->getCollection(), $lineMatcher);

        return Inertia::render('Admin/Royalties/Statements/Show', [
            'statement' => $statement,
            'lines' => $lines,
            'line_match_options' => $lineMatchOptions,
            'current_filter' => $matchFilter,
            'stats' => [
                'lines_count' => $linesCount,
                'matched_count' => $matchedCount,
                'unmatched_count' => $unmatchedCount,
                'ambiguous_count' => $ambiguousCount,
                'duplicate_count' => $duplicateCount,
                'reference_only_count' => $referenceOnlyCount,
            ],
        ]);
    }

    public function updateLineMatch(
        RoyaltyStatement $statement,
        RoyaltyStatementLine $line,
        UpdateRoyaltyStatementLineMatchRequest $request,
        RoyaltyAllocationService $allocationService
    ) {
        if ((int) $line->royalty_statement_id !== (int) $statement->id) {
            abort(404);
        }

        if ($statement->status === 'processing') {
            return back()->withErrors([
                'statement' => 'No puedes rematchear líneas mientras el statement se está procesando.',
            ]);
        }

        if ($statement->is_reference_only) {
            return back()->withErrors([
                'statement' => 'Este statement está en modo reference_only y no permite rematch manual.',
            ]);
        }

        $trackId = $request->validated('track_id');

        if ($trackId !== null) {
            $trackExists = Track::query()
                ->whereKey($trackId)
                ->exists();

            if (!$trackExists) {
                return back()->withErrors([
                    'track_id' => 'El track seleccionado no existe.',
                ]);
            }
        }

        $matchMeta = is_array($line->match_meta) ? $line->match_meta : [];
        $matchMeta['manual_override'] = true;
        $matchMeta['manual_override_by_user_id'] = (int) $request->user()->id;
        $matchMeta['manual_override_at'] = now()->toDateTimeString();

        $line->update([
            'track_id' => $trackId,
            'match_status' => $trackId
                ? RoyaltyStatementLine::MATCH_STATUS_MATCHED
                : RoyaltyStatementLine::MATCH_STATUS_UNMATCHED,
            'match_meta' => $matchMeta,
        ]);

        if ($statement->status === 'processed') {
            $allocationService->rebuildForStatement($statement->fresh(), [
                'trigger_source' => 'manual_line_match',
                'reason' => 'line_match_updated',
                'triggered_by_user_id' => (int) $request->user()->id,
                'context' => [
                    'statement_id' => (int) $statement->id,
                    'line_id' => (int) $line->id,
                    'track_id' => $trackId,
                ],
            ]);
        }

        return back()->with('success', 'Match de línea actualizado correctamente.');
    }

    public function download(RoyaltyStatement $statement)
    {
        $disk = Storage::disk('royalties_private');

        if (empty($statement->stored_path) || !$disk->exists($statement->stored_path)) {
            return back()->withErrors([
                'statement' => 'No se encontró el archivo original de este statement.',
            ]);
        }

        return $disk->download($statement->stored_path, $statement->original_filename);
    }

    public function store(
        StoreRoyaltyStatementRequest $request,
        MasterRoyaltyDedupeService $dedupeService
    )
    {
        $provider = $request->input('provider', 'symphonic');
        $file = $request->file('file');

        $originalFilename = $file->getClientOriginalName();
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            return back()
                ->withErrors(['file' => 'No se pudo leer el archivo.'])
                ->withInput();
        }
        $fileHash = $dedupeService->buildFileHash($contents);

        $exists = RoyaltyStatement::where('provider', $provider)
            ->where('file_hash', $fileHash)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['file' => 'Este archivo ya fue importado para este proveedor.'])
                ->withInput();
        }

        $now = now();
        $directory = sprintf(
            'royalties/%s/%s/%s',
            $provider,
            $now->format('Y'),
            $now->format('m')
        );

        $storedPath = Storage::disk('royalties_private')
            ->putFileAs($directory, $file, $originalFilename);

        if (!$storedPath) {
            return back()
                ->withErrors(['file' => 'No se pudo guardar el archivo. Intenta nuevamente.'])
                ->withInput();
        }

        RoyaltyStatement::create([
            'provider' => $provider,
            'currency' => 'USD',
            'original_filename' => $originalFilename,
            'stored_path' => $storedPath,
            'file_hash' => $fileHash,
            'status' => 'uploaded',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.royalties.statements.index')
            ->with('success', 'Statement subido correctamente.');
    }

    public function process(RoyaltyStatement $statement)
    {
        if (!in_array($statement->status, ['uploaded', 'failed'], true)) {
            return back()->withErrors([
                'statement' => 'Este statement ya fue procesado o está en proceso.',
            ]);
        }

        ProcessRoyaltyStatementJob::dispatch($statement->id);

        return redirect()
            ->route('admin.royalties.statements.index')
            ->with('success', $statement->status === 'failed'
                ? 'Reprocesamiento iniciado.'
                : 'Procesamiento iniciado.');
    }

    public function destroy(RoyaltyStatement $statement)
    {
        Gate::authorize('trash.manage.royalties');

        $statementKey = $statement->statement_key;
        $wasCurrent = (bool) $statement->is_current;

        DB::transaction(function () use ($statement): void {
            RoyaltyStatementLine::query()
                ->where('royalty_statement_id', $statement->id)
                ->get()
                ->each
                ->delete();

            $statement->delete();
        });

        if ($wasCurrent && !empty($statementKey)) {
            $this->promoteLatestCurrentStatement($statementKey);
        }

        return redirect()
            ->route('admin.royalties.statements.index')
            ->with('success', 'Statement movido a papelera.');
    }

    public function trash(Request $request)
    {
        Gate::authorize('trash.view.royalties');

        $statements = RoyaltyStatement::onlyTrashed()
            ->with('creator:id,name')
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn(RoyaltyStatement $statement) => [
                'id' => $statement->id,
                'primary' => $statement->original_filename,
                'secondary' => trim(strtoupper((string) $statement->provider) . ' · ' . ($statement->reporting_period ?? '-'), ' ·'),
                'deleted_at' => $statement->deleted_at,
                'can_force_delete' => true,
                'force_delete_blocked_reason' => null,
            ]);

        if ($request->expectsJson()) {
            return response()->json($statements);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Royalty Statements',
            'items' => $statements,
            'restoreRoute' => 'admin.royalties.statements.restore',
            'forceDeleteRoute' => 'admin.royalties.statements.force-delete',
            'backRoute' => 'admin.royalties.statements.index',
        ]);
    }

    public function restore(int $statementId)
    {
        Gate::authorize('trash.manage.royalties');

        $statement = RoyaltyStatement::onlyTrashed()->findOrFail($statementId);
        $statementKey = $statement->statement_key;

        DB::transaction(function () use ($statement): void {
            if ($statement->is_current && !empty($statement->statement_key)) {
                $currentExists = RoyaltyStatement::query()
                    ->where('statement_key', $statement->statement_key)
                    ->where('id', '!=', $statement->id)
                    ->where('is_current', true)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($currentExists) {
                    $statement->is_current = false;
                    $statement->save();
                }
            }

            $statement->restore();

            RoyaltyStatementLine::onlyTrashed()
                ->where('royalty_statement_id', $statement->id)
                ->restore();
        });

        if (!empty($statementKey)) {
            $this->promoteLatestCurrentStatement($statementKey);
        }

        return redirect()
            ->route('admin.royalties.statements.index')
            ->with('success', 'Statement restaurado correctamente.');
    }

    public function forceDelete(int $statementId)
    {
        Gate::authorize('trash.manage.royalties');

        $statement = RoyaltyStatement::withTrashed()->findOrFail($statementId);
        $statementKey = $statement->statement_key;
        $wasCurrent = (bool) $statement->is_current;

        if (!$statement->trashed()) {
            return back()->withErrors([
                'statement' => 'Solo puedes eliminar permanentemente statements en papelera.',
            ]);
        }

        DB::transaction(function () use ($statement): void {
            RoyaltyStatementLine::withTrashed()
                ->where('royalty_statement_id', $statement->id)
                ->get()
                ->each
                ->forceDelete();

            if (!empty($statement->stored_path)) {
                $disk = Storage::disk('royalties_private');
                if ($disk->exists($statement->stored_path)) {
                    $disk->delete($statement->stored_path);
                }
            }

            $statement->forceDelete();
        });

        if ($wasCurrent && !empty($statementKey)) {
            $this->promoteLatestCurrentStatement($statementKey);
        }

        return redirect()
            ->route('admin.royalties.statements.index')
            ->with('success', 'Statement eliminado permanentemente.');
    }

    private function promoteLatestCurrentStatement(string $statementKey): void
    {
        if ($statementKey === '') {
            return;
        }

        $currentExists = RoyaltyStatement::query()
            ->where('statement_key', $statementKey)
            ->whereNull('deleted_at')
            ->where('is_current', true)
            ->exists();

        if ($currentExists) {
            return;
        }

        $candidate = RoyaltyStatement::query()
            ->where('statement_key', $statementKey)
            ->whereNull('deleted_at')
            ->where('status', 'processed')
            ->where('is_reference_only', false)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first(['id']);

        if (!$candidate) {
            return;
        }

        $now = now();

        RoyaltyStatement::query()
            ->where('statement_key', $statementKey)
            ->whereNull('deleted_at')
            ->update([
                'is_current' => false,
                'updated_at' => $now,
            ]);

        RoyaltyStatement::query()
            ->whereKey($candidate->id)
            ->update([
                'is_current' => true,
                'updated_at' => $now,
            ]);
    }

    private function normalizeMatchFilter(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['all', 'review', ...RoyaltyStatementLine::allowedMatchStatuses()], true)
            ? $value
            : 'all';
    }

    private function buildLineMatchOptions(
        Collection $lines,
        MasterRoyaltyLineMatcher $lineMatcher
    ): array
    {
        $options = [];

        foreach ($lines as $line) {
            if ($line->track_id || $line->match_status === RoyaltyStatementLine::MATCH_STATUS_REFERENCE_ONLY) {
                continue;
            }

            $options[(int) $line->id] = $lineMatcher->suggestCandidates(
                $line->isrc,
                $line->upc,
                $line->track_title
            );
        }

        return $options;
    }
}
