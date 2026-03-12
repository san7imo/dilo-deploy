<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompositionRoyaltyStatementRequest;
use App\Jobs\ProcessCompositionRoyaltyStatementJob;
use App\Models\CompositionAllocation;
use App\Models\CompositionRoyaltyLine;
use App\Models\CompositionRoyaltyStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompositionRoyaltyStatementController extends Controller
{
    public function index()
    {
        $statements = CompositionRoyaltyStatement::query()
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Admin/Royalties/CompositionStatements/Index', [
            'statements' => $statements,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Royalties/CompositionStatements/Create', [
            'providers' => [
                ['value' => 'manual_dilo', 'label' => 'Manual Dilo (CSV)'],
            ],
        ]);
    }

    public function store(StoreCompositionRoyaltyStatementRequest $request)
    {
        $provider = (string) $request->validated('provider');
        $reportingPeriod = trim((string) ($request->validated('reporting_period') ?? ''));
        if ($reportingPeriod === '') {
            $reportingPeriod = strtoupper(now()->format('M-y'));
        }

        $sourceName = $request->validated('source_name') ?: 'manual_dilo_template';
        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            return back()->withErrors([
                'file' => 'No se pudo leer el archivo subido.',
            ]);
        }

        $fileHash = $this->buildFileHash($contents);
        $existing = CompositionRoyaltyStatement::withTrashed()
            ->where('provider', $provider)
            ->where('file_hash', $fileHash)
            ->first();

        if ($existing) {
            return back()->withErrors([
                'file' => 'Este archivo ya fue cargado anteriormente para composición.',
            ])->withInput();
        }

        $statementKey = $this->buildStatementKey($provider, $reportingPeriod);

        $nextVersion = ((int) CompositionRoyaltyStatement::query()
            ->where('statement_key', $statementKey)
            ->max('version')) + 1;

        $now = now();
        $directory = sprintf('composition-royalties/%s/%s', $now->format('Y'), $now->format('m'));
        $storedFilename = sprintf('%s_%s', $now->format('Ymd_His_u'), $originalFilename);
        $storedPath = Storage::disk('royalties_private')
            ->putFileAs($directory, $file, $storedFilename);

        if (!$storedPath) {
            return back()->withErrors([
                'file' => 'No se pudo guardar el archivo en storage privado.',
            ])->withInput();
        }

        $statement = DB::transaction(function () use (
            $provider,
            $sourceName,
            $reportingPeriod,
            $originalFilename,
            $storedPath,
            $fileHash,
            $statementKey,
            $nextVersion,
            $request
        ) {
            CompositionRoyaltyStatement::query()
                ->where('statement_key', $statementKey)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            return CompositionRoyaltyStatement::query()->create([
                'provider' => $provider,
                'source_name' => $sourceName,
                'reporting_period' => $reportingPeriod,
                'currency' => 'USD',
                'original_filename' => $originalFilename,
                'stored_path' => $storedPath,
                'file_hash' => $fileHash,
                'statement_key' => $statementKey,
                'version' => $nextVersion,
                'is_current' => true,
                'status' => CompositionRoyaltyStatement::STATUS_UPLOADED,
                'created_by' => $request->user()->id,
            ]);
        });

        ProcessCompositionRoyaltyStatementJob::dispatch((int) $statement->id);

        return redirect()
            ->route('admin.royalties.composition-statements.show', $statement->id)
            ->with('success', 'Statement de composición cargado y enviado a procesamiento.');
    }

    public function show(CompositionRoyaltyStatement $statement)
    {
        $statement->load('creator:id,name');

        $lines = CompositionRoyaltyLine::query()
            ->where('composition_royalty_statement_id', $statement->id)
            ->with('composition:id,title,iswc')
            ->orderBy('id')
            ->paginate(50);

        $matchCounts = CompositionRoyaltyLine::query()
            ->where('composition_royalty_statement_id', $statement->id)
            ->selectRaw('match_status, COUNT(*) as total')
            ->groupBy('match_status')
            ->pluck('total', 'match_status');

        $allocationSummary = CompositionAllocation::query()
            ->where('composition_royalty_statement_id', $statement->id)
            ->selectRaw('COUNT(*) as total_rows, COALESCE(SUM(allocated_amount_usd),0) as total_allocated_usd')
            ->first();

        return Inertia::render('Admin/Royalties/CompositionStatements/Show', [
            'statement' => $statement,
            'lines' => $lines,
            'stats' => [
                'lines_total' => (int) ($statement->total_lines ?? 0),
                'matched_count' => (int) ($matchCounts[CompositionRoyaltyLine::MATCH_STATUS_MATCHED] ?? 0),
                'unmatched_count' => (int) ($matchCounts[CompositionRoyaltyLine::MATCH_STATUS_UNMATCHED] ?? 0),
                'ambiguous_count' => (int) ($matchCounts[CompositionRoyaltyLine::MATCH_STATUS_AMBIGUOUS] ?? 0),
                'allocations_count' => (int) ($allocationSummary->total_rows ?? 0),
                'allocations_total_usd' => (float) ($allocationSummary->total_allocated_usd ?? 0),
            ],
        ]);
    }

    public function process(CompositionRoyaltyStatement $statement)
    {
        if (!in_array($statement->status, [
            CompositionRoyaltyStatement::STATUS_UPLOADED,
            CompositionRoyaltyStatement::STATUS_FAILED,
        ], true)) {
            return back()->withErrors([
                'statement' => 'Solo se pueden procesar statements en estado uploaded/failed.',
            ]);
        }

        ProcessCompositionRoyaltyStatementJob::dispatch((int) $statement->id);

        return back()->with('success', 'Reprocesamiento de composición encolado.');
    }

    public function download(CompositionRoyaltyStatement $statement)
    {
        $disk = Storage::disk('royalties_private');

        if (empty($statement->stored_path) || !$disk->exists($statement->stored_path)) {
            return back()->withErrors([
                'statement' => 'No se encontró el archivo original del statement.',
            ]);
        }

        return $disk->download($statement->stored_path, $statement->original_filename);
    }

    public function template()
    {
        $headers = [
            'reporting_period',
            'activity_period',
            'activity_month',
            'line_type',
            'composition_id',
            'composition_iswc',
            'composition_title',
            'source_name',
            'territory_code',
            'units',
            'amount_usd',
            'currency',
            'source_line_id',
            'external_reference',
        ];

        $rows = [
            [
                strtoupper(now()->format('M-y')),
                now()->format('F Y'),
                now()->startOfMonth()->toDateString(),
                'performance',
                '',
                '',
                'Example Composition',
                'ASCAP',
                'US',
                '1200',
                '100.00',
                'USD',
                'perf-001',
                'ref-perf-001',
            ],
            [
                strtoupper(now()->format('M-y')),
                now()->format('F Y'),
                now()->startOfMonth()->toDateString(),
                'mechanical',
                '',
                '',
                'Example Composition',
                'MLC',
                'US',
                '800',
                '40.00',
                'USD',
                'mech-001',
                'ref-mech-001',
            ],
        ];

        return response()->streamDownload(function () use ($headers, $rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, 'dilo-composition-royalties-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildFileHash(string $rawContents): string
    {
        $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $rawContents) ?? $rawContents;
        $normalized = str_replace(["\r\n", "\r"], "\n", $normalized);

        return hash('sha256', $normalized);
    }

    private function buildStatementKey(string $provider, string $reportingPeriod): string
    {
        return strtolower(trim($provider) . '|' . trim($reportingPeriod));
    }
}

