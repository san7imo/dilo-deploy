<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackSplitAgreementRequest;
use App\Models\Artist;
use App\Models\Track;
use App\Models\TrackSplitAgreement;
use App\Models\TrackSplitParticipant;
use Illuminate\Support\Facades\DB;
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
        return Inertia::render('Admin/Tracks/Splits/Create', [
            'track' => $track->load('release:id,title'),
            'artists' => Artist::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTrackSplitAgreementRequest $request, Track $track)
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

        DB::transaction(function () use ($track, $data, $storedPath, $originalFilename, $hash, $request) {
            TrackSplitAgreement::where('track_id', $track->id)
                ->where('split_type', $data['split_type'])
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            $agreement = TrackSplitAgreement::create([
                'track_id' => $track->id,
                'split_type' => $data['split_type'],
                'status' => 'active',
                'contract_path' => $storedPath,
                'contract_original_filename' => $originalFilename,
                'contract_hash' => $hash,
                'created_by' => $request->user()->id,
            ]);

            $participants = collect($data['participants'])->map(function ($participant) use ($agreement) {
                return [
                    'track_split_agreement_id' => $agreement->id,
                    'artist_id' => $participant['artist_id'] ?? null,
                    'payee_email' => $participant['payee_email'] ?? null,
                    'name' => $participant['name'] ?? null,
                    'role' => strtolower(trim((string) $participant['role'])),
                    'percentage' => $participant['percentage'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            TrackSplitParticipant::insert($participants);
        });

        return redirect()
            ->route('admin.tracks.splits.index', $track->id)
            ->with('success', 'Split creado correctamente.');
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
