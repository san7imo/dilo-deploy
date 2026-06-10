<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackSplitAgreementRequest;
use App\Models\Artist;
use App\Models\RoyaltyAllocation;
use App\Models\RoyaltyStatement;
use App\Models\Track;
use App\Models\TrackSplitAgreement;
use App\Models\TrackSplitParticipant;
use App\Models\User;
use App\Services\ExternalArtistInvitationService;
use App\Services\RoyaltyAllocationService;
use App\Services\SplitArtistOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TrackSplitAgreementController extends Controller
{
    public function index(Track $track)
    {
        $agreements = TrackSplitAgreement::query()
            ->where('track_id', $track->id)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Tracks/Splits/Index', [
            'track' => $track->load('release:id,title'),
            'agreements' => $agreements,
        ]);
    }

    public function create(Track $track, SplitArtistOptionService $artistOptionService)
    {
        $artistOptions = $artistOptionService->grouped();

        return Inertia::render('Admin/Tracks/Splits/Create', [
            'track' => $track->load('release:id,title'),
            'internalArtists' => $artistOptions['internalArtists'],
            'externalArtists' => $artistOptions['externalArtists'],
            'initialParticipants' => [],
            'correction' => null,
        ]);
    }

    public function correct(Track $track, SplitArtistOptionService $artistOptionService)
    {
        $agreement = TrackSplitAgreement::query()
            ->where('track_id', $track->id)
            ->where('status', 'active')
            ->with(['participants.artist:id,artist_origin'])
            ->latest('id')
            ->first();

        if (!$agreement) {
            return redirect()
                ->route('admin.tracks.splits.create', $track->id)
                ->with('warning', 'No hay un split activo para corregir. Crea un split nuevo.');
        }

        $artistOptions = $artistOptionService->grouped();
        $allocationCount = RoyaltyAllocation::query()
            ->where('track_split_agreement_id', $agreement->id)
            ->count();

        return Inertia::render('Admin/Tracks/Splits/Create', [
            'track' => $track->load('release:id,title'),
            'internalArtists' => $artistOptions['internalArtists'],
            'externalArtists' => $artistOptions['externalArtists'],
            'initialParticipants' => $agreement->participants
                ->sortBy('id')
                ->values()
                ->map(fn (TrackSplitParticipant $participant): array => $this->serializeParticipantForCorrection($participant))
                ->all(),
            'correction' => [
                'source_agreement_id' => $agreement->id,
                'split_type' => $agreement->split_type,
                'effective_from' => $agreement->effective_from?->toDateString(),
                'effective_to' => $agreement->effective_to?->toDateString(),
                'allocation_count' => $allocationCount,
                'contract_original_filename' => $agreement->contract_original_filename,
            ],
        ]);
    }

    public function store(
        StoreTrackSplitAgreementRequest $request,
        Track $track,
        ExternalArtistInvitationService $invitationService
    )
    {
        $data = $request->validated();
        $contract = $request->file('contract');

        $originalFilename = $contract->getClientOriginalName();
        $hash = hash_file('sha256', $contract->getRealPath());
        $now = now();
        $directory = sprintf(
            'splits/%s/%s/%s',
            $track->id,
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

        $createdParticipants = collect();

        DB::transaction(function () use ($track, $data, $storedPath, $originalFilename, $hash, $request, &$createdParticipants) {
            $artistIds = collect($data['participants'])
                ->pluck('artist_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            $artistsById = Artist::query()
                ->whereIn('id', $artistIds)
                ->with('user:id,name,stage_name,email')
                ->get(['id', 'name', 'user_id'])
                ->keyBy('id');

            $participantEmails = collect($data['participants'])
                ->pluck('payee_email')
                ->filter()
                ->map(fn($email) => strtolower(trim((string) $email)))
                ->unique()
                ->values();

            $usersByEmail = User::query()
                ->whereIn('email', $participantEmails)
                ->get(['id', 'email'])
                ->mapWithKeys(fn(User $user) => [strtolower($user->email) => $user->id]);

            TrackSplitAgreement::where('track_id', $track->id)
                ->where('split_type', $data['split_type'])
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            $agreement = TrackSplitAgreement::create([
                'track_id' => $track->id,
                'split_type' => $data['split_type'],
                'status' => 'active',
                'effective_from' => $data['effective_from'] ?? null,
                'effective_to' => $data['effective_to'] ?? null,
                'contract_path' => $storedPath,
                'contract_original_filename' => $originalFilename,
                'contract_hash' => $hash,
                'created_by' => $request->user()->id,
            ]);

            $participants = collect($data['participants'])->map(function ($participant) use ($agreement, $artistsById, $usersByEmail) {
                $email = !empty($participant['payee_email'])
                    ? strtolower(trim((string) $participant['payee_email']))
                    : null;

                $artistId = !empty($participant['artist_id'])
                    ? (int) $participant['artist_id']
                    : null;

                $selectedArtist = $artistId ? $artistsById->get($artistId) : null;
                $selectedUser = $selectedArtist?->user;

                $userId = $selectedUser?->id
                    ? (int) $selectedUser->id
                    : (!empty($participant['user_id']) ? (int) $participant['user_id'] : null);
                if ($selectedUser) {
                    $selectedUserEmail = strtolower(trim((string) ($selectedUser->email ?? '')));
                    if (!$email && $selectedUserEmail !== '') {
                        $email = $selectedUserEmail;
                    }

                    if (empty($participant['name'])) {
                        $participant['name'] = $selectedUser->stage_name ?: $selectedUser->name;
                    }
                } elseif (!$userId && $email) {
                    $userId = $usersByEmail[$email] ?? null;
                } elseif ($selectedArtist && empty($participant['name'])) {
                    $participant['name'] = $selectedArtist->name;
                }

                if ($email !== null && trim($email) === '') {
                    $email = null;
                }

                return [
                    'track_split_agreement_id' => $agreement->id,
                    'artist_id' => $artistId,
                    'user_id' => $userId,
                    'payee_email' => $email,
                    'name' => $participant['name'] ?? null,
                    'role' => strtolower(trim((string) $participant['role'])),
                    'percentage' => $participant['percentage'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            $createdParticipants = $agreement->participants()->createMany($participants);
        });

        $invitationResult = $invitationService->inviteForParticipants(
            $track,
            $createdParticipants,
            $request->user()
        );

        $message = 'Split creado correctamente.';
        if (($invitationResult['sent'] ?? 0) > 0) {
            $message .= " Se enviaron {$invitationResult['sent']} invitaciones a artistas externos.";
        }

        return redirect()
            ->route('admin.tracks.splits.index', $track->id)
            ->with('success', $message);
    }

    public function download(Track $track, TrackSplitAgreement $agreement)
    {
        if ($agreement->track_id !== $track->id) {
            abort(404);
        }

        $disk = Storage::disk('contracts_private');
        if (!$disk->exists($agreement->contract_path)) {
            abort(404);
        }

        return $disk->download($agreement->contract_path, $agreement->contract_original_filename);
    }

    public function recalculate(Track $track, Request $request, RoyaltyAllocationService $allocationService)
    {
        $statements = RoyaltyStatement::query()
            ->where('status', 'processed')
            ->whereHas('lines', fn ($query) => $query->where('track_id', $track->id))
            ->orderBy('id')
            ->get();

        if ($statements->isEmpty()) {
            return back()->with('warning', 'No hay statements master procesados afectados por este track.');
        }

        $statementIds = $statements->pluck('id')->values();
        if ($this->hasLockedMasterAllocations($statementIds)) {
            return back()->withErrors([
                'recalculate' => 'No se puede recalcular: hay allocations del statement con estado distinto de accrued o asociadas a pagos.',
            ]);
        }

        $totals = [
            'statements' => 0,
            'lines_matched' => 0,
            'master_allocations_count' => 0,
            'warnings_count' => 0,
        ];

        foreach ($statements as $statement) {
            $stats = $allocationService->rebuildForStatement($statement->fresh(), [
                'trigger_source' => 'manual_track_split_correction',
                'reason' => 'track_split_recalculation_requested',
                'triggered_by_user_id' => (int) $request->user()->id,
                'context' => [
                    'track_id' => (int) $track->id,
                    'statement_ids' => $statementIds->all(),
                ],
            ]);

            $totals['statements']++;
            $totals['lines_matched'] += (int) ($stats['lines_matched'] ?? 0);
            $totals['master_allocations_count'] += (int) ($stats['master_allocations_count'] ?? 0);
            $totals['warnings_count'] += count($stats['warnings'] ?? []);
        }

        return back()->with(
            'success',
            "Recálculo master completado: {$totals['statements']} statements, {$totals['lines_matched']} líneas matched y {$totals['master_allocations_count']} allocations master generadas."
        );
    }

    private function serializeParticipantForCorrection(TrackSplitParticipant $participant): array
    {
        $artistOrigin = $participant->artist?->artist_origin;

        $participantType = match ($artistOrigin) {
            'internal' => 'internal',
            'external' => 'external_existing',
            default => $participant->payee_email ? 'external_new' : 'manual',
        };

        return [
            'role' => $participant->role,
            'percentage' => $participant->percentage,
            'participant_type' => $participantType,
            'artist_id' => $participant->artist_id ?: '',
            'user_id' => $participant->artist_id ? '' : ($participant->user_id ?: ''),
            'name' => $participant->name ?: '',
            'payee_email' => $participant->payee_email ?: '',
        ];
    }

    private function hasLockedMasterAllocations($statementIds): bool
    {
        if (!Schema::hasTable('royalty_allocations')) {
            return false;
        }

        $lockedByStatus = DB::table('royalty_allocations')
            ->whereIn('royalty_statement_id', $statementIds)
            ->where('status', '!=', 'accrued')
            ->exists();

        if ($lockedByStatus) {
            return true;
        }

        if (!Schema::hasTable('royalty_payout_items')) {
            return false;
        }

        return DB::table('royalty_payout_items as rpi')
            ->join('royalty_allocations as ra', 'ra.id', '=', 'rpi.royalty_allocation_id')
            ->whereIn('ra.royalty_statement_id', $statementIds)
            ->exists();
    }
}
