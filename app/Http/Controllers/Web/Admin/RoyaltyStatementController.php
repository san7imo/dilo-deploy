<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoyaltyStatementRequest;
use App\Jobs\ProcessRoyaltyStatementJob;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RoyaltyStatementController extends Controller
{
    public function index(Request $request)
    {
        $statements = RoyaltyStatement::with('creator:id,name')
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
            ],
        ]);
    }

    public function show(RoyaltyStatement $statement)
    {
        $statement->load('creator:id,name');

        $linesQuery = RoyaltyStatementLine::query()
            ->where('royalty_statement_id', $statement->id);

        $lines = (clone $linesQuery)
            ->select([
                'id',
                'royalty_statement_id',
                'track_id',
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
            ->paginate(50);

        $linesCount = (clone $linesQuery)->count();
        $unmatchedCount = (clone $linesQuery)->whereNull('track_id')->count();
        $matchedCount = $linesCount - $unmatchedCount;

        return Inertia::render('Admin/Royalties/Statements/Show', [
            'statement' => $statement,
            'lines' => $lines,
            'stats' => [
                'lines_count' => $linesCount,
                'matched_count' => $matchedCount,
                'unmatched_count' => $unmatchedCount,
            ],
        ]);
    }

    public function store(StoreRoyaltyStatementRequest $request)
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
        $normalized = $this->normalizeContents($contents);
        $fileHash = hash('sha256', $normalized);

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
        if ($statement->status !== 'uploaded') {
            return back()->withErrors([
                'statement' => 'Este statement ya fue procesado o está en proceso.',
            ]);
        }

        ProcessRoyaltyStatementJob::dispatch($statement->id);

        return redirect()
            ->route('admin.royalties.statements.index')
            ->with('success', 'Procesamiento iniciado.');
    }

    private function normalizeContents(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        return str_replace(["\r\n", "\r"], "\n", $contents);
    }
}
