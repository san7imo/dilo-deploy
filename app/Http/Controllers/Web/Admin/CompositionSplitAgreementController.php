<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompositionSplitAgreementRequest;
use App\Models\CompositionAllocation;
use App\Models\Composition;
use App\Models\CompositionRoyaltyStatement;
use App\Models\CompositionSplitParticipant;
use App\Models\CompositionSplitSet;
use App\Models\RoyaltyAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Services\Compositions\CompositionRoyaltyAllocationService;
use App\Services\Compositions\CompositionSplitSetService;
use App\Services\SplitArtistOptionService;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CompositionSplitAgreementController extends Controller
{
    public function index(Request $request, Composition $composition)
    {
        $agreements = CompositionSplitSet::query()
            ->where('composition_id', $composition->id)
            ->withCount([
                'participants',
                'participants as writers_count' => fn($query) => $query->where('share_pool', 'writer'),
                'participants as publishers_count' => fn($query) => $query->where('share_pool', 'publisher'),
                'participants as mechanical_payees_count' => fn($query) => $query->where('share_pool', 'mechanical_payee'),
            ])
            ->orderByDesc('version')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Compositions/Splits/Index', [
            'composition' => $composition,
            'agreements' => $agreements,
            'back_url' => $request->query('back'),
        ]);
    }

    public function create(Request $request, Composition $composition, SplitArtistOptionService $artistOptionService)
    {
        $artistOptions = $artistOptionService->grouped();

        return Inertia::render('Admin/Compositions/Splits/Create', [
            'composition' => $composition,
            'internalArtists' => $artistOptions['internalArtists'],
            'externalArtists' => $artistOptions['externalArtists'],
            'initialParticipants' => [],
            'correction' => null,
            'back_url' => $request->query('back'),
        ]);
    }

    public function correct(Request $request, Composition $composition, SplitArtistOptionService $artistOptionService)
    {
        $splitSet = CompositionSplitSet::query()
            ->where('composition_id', $composition->id)
            ->where('status', 'active')
            ->with(['participants.artist:id,artist_origin'])
            ->latest('version')
            ->latest('id')
            ->first();

        if (!$splitSet) {
            return redirect()
                ->route('admin.compositions.splits.create', [
                    'composition' => $composition->id,
                    'back' => $request->query('back'),
                ])
                ->with('warning', 'No hay un split activo para corregir. Crea un split nuevo.');
        }

        $participantIds = $splitSet->participants->pluck('id')->values();
        $royaltyAllocationCount = RoyaltyAllocation::query()
            ->where('composition_split_set_id', $splitSet->id)
            ->orWhereIn('composition_split_participant_id', $participantIds)
            ->count();
        $compositionAllocationCount = CompositionAllocation::query()
            ->where('composition_split_set_id', $splitSet->id)
            ->orWhereIn('composition_split_participant_id', $participantIds)
            ->count();

        $artistOptions = $artistOptionService->grouped();

        return Inertia::render('Admin/Compositions/Splits/Create', [
            'composition' => $composition,
            'internalArtists' => $artistOptions['internalArtists'],
            'externalArtists' => $artistOptions['externalArtists'],
            'initialParticipants' => $splitSet->participants
                ->sortBy('id')
                ->values()
                ->map(fn (CompositionSplitParticipant $participant): array => $this->serializeParticipantForCorrection($participant))
                ->all(),
            'correction' => [
                'source_split_set_id' => $splitSet->id,
                'version' => $splitSet->version,
                'effective_from' => $splitSet->effective_from?->toDateString(),
                'effective_to' => $splitSet->effective_to?->toDateString(),
                'allocation_count' => $royaltyAllocationCount + $compositionAllocationCount,
                'contract_original_filename' => $splitSet->contract_original_filename,
            ],
            'back_url' => $request->query('back'),
        ]);
    }

    public function store(
        StoreCompositionSplitAgreementRequest $request,
        Composition $composition,
        CompositionSplitSetService $splitSetService
    ) {
        $data = $request->validated();
        $contract = $request->file('contract');

        $originalFilename = $contract->getClientOriginalName();
        $hash = hash_file('sha256', $contract->getRealPath());
        $now = now();
        $directory = sprintf(
            'composition-splits/%s/%s/%s',
            $composition->id,
            $now->format('Y'),
            $now->format('m')
        );

        $storedPath = Storage::disk('contracts_private')
            ->putFileAs($directory, $contract, $originalFilename);

        if (!$storedPath) {
            return back()
                ->withErrors(['contract' => 'No se pudo guardar el contrato.'])
                ->withInput();
        }

        try {
            $splitSetService->createVersionedSet(
                $composition,
                $data,
                [
                    'path' => $storedPath,
                    'original_filename' => $originalFilename,
                    'hash' => $hash,
                ],
                (int) $request->user()->id
            );
        } catch (ValidationException $exception) {
            Storage::disk('contracts_private')->delete($storedPath);
            throw $exception;
        } catch (\Throwable $exception) {
            Storage::disk('contracts_private')->delete($storedPath);
            throw $exception;
        }

        return redirect()
            ->route('admin.compositions.splits.index', $composition->id)
            ->with('success', 'Split de composición creado correctamente.');
    }

    public function download(Composition $composition, CompositionSplitSet $agreement)
    {
        if ($agreement->composition_id !== $composition->id) {
            abort(404);
        }

        $disk = Storage::disk('contracts_private');
        if (!$disk->exists($agreement->contract_path)) {
            abort(404);
        }

        return $disk->download($agreement->contract_path, $agreement->contract_original_filename);
    }

    public function recalculate(
        Composition $composition,
        Request $request,
        CompositionRoyaltyAllocationService $allocationService
    ) {
        $statements = CompositionRoyaltyStatement::query()
            ->where('status', CompositionRoyaltyStatement::STATUS_PROCESSED)
            ->whereHas('lines', fn ($query) => $query->where('composition_id', $composition->id))
            ->orderBy('id')
            ->get();

        if ($statements->isEmpty()) {
            return back()->with('warning', 'No hay statements de composición procesados afectados por esta composición.');
        }

        $statementIds = $statements->pluck('id')->values();
        if ($this->hasLockedCompositionAllocations($statementIds)) {
            return back()->withErrors([
                'recalculate' => 'No se puede recalcular: hay allocations de composición con estado distinto de accrued.',
            ]);
        }

        $totals = [
            'statements' => 0,
            'lines_matched' => 0,
            'allocations_count' => 0,
            'warnings_count' => 0,
        ];

        foreach ($statements as $statement) {
            $stats = $allocationService->rebuildForStatement($statement->fresh(), [
                'trigger_source' => 'manual_composition_split_correction',
                'reason' => 'composition_split_recalculation_requested',
                'triggered_by_user_id' => (int) $request->user()->id,
                'context' => [
                    'composition_id' => (int) $composition->id,
                    'statement_ids' => $statementIds->all(),
                ],
            ]);

            $totals['statements']++;
            $totals['lines_matched'] += (int) ($stats['lines_matched'] ?? 0);
            $totals['allocations_count'] += (int) ($stats['allocations_count'] ?? 0);
            $totals['warnings_count'] += count($stats['warnings'] ?? []);
        }

        return back()->with(
            'success',
            "Recálculo de composición completado: {$totals['statements']} statements, {$totals['lines_matched']} líneas matched y {$totals['allocations_count']} allocations generadas."
        );
    }

    private function serializeParticipantForCorrection(CompositionSplitParticipant $participant): array
    {
        $artistOrigin = $participant->artist?->artist_origin;

        $participantType = match ($artistOrigin) {
            'internal' => 'internal',
            'external' => 'external_existing',
            default => $participant->payee_email ? 'external_new' : 'manual',
        };

        return [
            'role' => $participant->role,
            'share_pool' => $participant->share_pool,
            'percentage' => $participant->percentage,
            'participant_type' => $participantType,
            'artist_id' => $participant->artist_id ?: '',
            'user_id' => $participant->artist_id ? '' : ($participant->user_id ?: ''),
            'name' => $participant->name ?: '',
            'payee_email' => $participant->payee_email ?: '',
        ];
    }

    private function hasLockedCompositionAllocations($statementIds): bool
    {
        if (!Schema::hasTable('composition_allocations')) {
            return false;
        }

        return DB::table('composition_allocations')
            ->whereIn('composition_royalty_statement_id', $statementIds)
            ->where('status', '!=', 'accrued')
            ->exists();
    }
}
