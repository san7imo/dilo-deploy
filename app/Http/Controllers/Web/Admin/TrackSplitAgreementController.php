<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackSplitAgreementRequest;
use App\Models\Artist;
use App\Models\Track;
use App\Models\TrackSplitAgreement;
use App\Models\TrackSplitParticipant;
use App\Models\User;
use App\Services\ExternalArtistInvitationService;
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

    public function create(Track $track)
    {
        $externalArtists = collect();
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $externalArtists = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'external_artist')
                        ->where('guard_name', 'web');
                })
                ->orderByRaw("COALESCE(NULLIF(stage_name, ''), name)")
                ->get(['id', 'name', 'stage_name', 'email']);
        }

        return Inertia::render('Admin/Tracks/Splits/Create', [
            'track' => $track->load('release:id,title'),
            'artists' => Artist::orderBy('name')->get(['id', 'name']),
            'externalArtists' => $externalArtists,
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

            $artistUserMap = Artist::query()
                ->whereIn('id', $artistIds)
                ->pluck('user_id', 'id')
                ->map(fn($value) => $value ? (int) $value : null);

            $participantEmails = collect($data['participants'])
                ->pluck('payee_email')
                ->filter()
                ->map(fn($email) => strtolower(trim((string) $email)))
                ->unique()
                ->values();

            $selectedUserIds = collect($data['participants'])
                ->pluck('user_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            $selectedUsers = User::query()
                ->whereIn('id', $selectedUserIds)
                ->get(['id', 'name', 'stage_name', 'email'])
                ->keyBy('id');

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

            $participants = collect($data['participants'])->map(function ($participant) use ($agreement, $artistUserMap, $usersByEmail, $selectedUsers) {
                $email = !empty($participant['payee_email'])
                    ? strtolower(trim((string) $participant['payee_email']))
                    : null;

                $artistId = !empty($participant['artist_id'])
                    ? (int) $participant['artist_id']
                    : null;

                $selectedUserId = !empty($participant['user_id'])
                    ? (int) $participant['user_id']
                    : null;

                $userId = $selectedUserId ?: ($artistId ? ($artistUserMap[$artistId] ?? null) : null);
                if ($userId && $selectedUsers->has($userId)) {
                    $selectedUser = $selectedUsers->get($userId);
                    $selectedUserEmail = strtolower(trim((string) ($selectedUser->email ?? '')));
                    if (!$email && $selectedUserEmail !== '') {
                        $email = $selectedUserEmail;
                    }

                    if (empty($participant['name'])) {
                        $participant['name'] = $selectedUser->stage_name ?: $selectedUser->name;
                    }
                } elseif (!$userId && $email) {
                    $userId = $usersByEmail[$email] ?? null;
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
}
