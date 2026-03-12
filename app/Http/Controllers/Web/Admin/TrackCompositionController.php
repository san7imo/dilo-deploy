<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompositionRequest;
use App\Models\Composition;
use App\Models\Track;
use App\Services\Compositions\CompositionCatalogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TrackCompositionController extends Controller
{
    public function index(Track $track)
    {
        $linkedCompositions = $track->compositions()
            ->withCount(['splitSets', 'tracks'])
            ->orderBy('title')
            ->get(['compositions.id', 'compositions.title', 'compositions.iswc', 'compositions.created_at']);

        $availableCompositions = Composition::query()
            ->whereDoesntHave('tracks', function ($query) use ($track) {
                $query->where('tracks.id', $track->id);
            })
            ->orderBy('title')
            ->get(['id', 'title', 'iswc']);

        return Inertia::render('Admin/Tracks/Compositions/Index', [
            'track' => $track->load('release:id,title'),
            'compositions' => $linkedCompositions,
            'available_compositions' => $availableCompositions,
        ]);
    }

    public function store(
        StoreCompositionRequest $request,
        Track $track,
        CompositionCatalogService $catalogService
    ) {
        $payload = $request->validated();
        $payload['track_ids'] = collect($payload['track_ids'] ?? [])
            ->push($track->id)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $catalogService->create($payload, (int) $request->user()->id);

        return redirect()
            ->route('admin.tracks.compositions.index', $track->id)
            ->with('success', 'Composición creada y vinculada al track.');
    }

    public function attach(Request $request, Track $track)
    {
        $data = $request->validate([
            'composition_id' => [
                'required',
                'integer',
                Rule::exists('compositions', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $compositionId = (int) $data['composition_id'];
        $alreadyLinked = $track->compositions()->where('compositions.id', $compositionId)->exists();

        if (!$alreadyLinked) {
            $track->compositions()->syncWithoutDetaching([
                $compositionId => [
                    'relationship_type' => 'main_work',
                    'source' => 'track_composition_hub',
                ],
            ]);
        }

        return redirect()
            ->route('admin.tracks.compositions.index', $track->id)
            ->with('success', $alreadyLinked
                ? 'La composición ya estaba vinculada a este track.'
                : 'Composición vinculada al track correctamente.');
    }

    public function detach(Track $track, Composition $composition)
    {
        $track->compositions()->detach($composition->id);

        return redirect()
            ->route('admin.tracks.compositions.index', $track->id)
            ->with('success', 'Composición desvinculada del track.');
    }
}
