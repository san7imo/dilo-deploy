<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Services\ReleaseService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    protected ReleaseService $releaseService;

    public function __construct(ReleaseService $releaseService)
    {
        $this->releaseService = $releaseService;
    }

    public function index(Request $request)
    {
        $artist = $request->user()->artist()->firstOrFail();
        $releases = Release::where('artist_id', $artist->id)
            ->orderByDesc('release_date')
            ->paginate(10, ['id', 'title', 'type', 'release_date', 'cover_url']);

        return Inertia::render('Artist/Releases/Index', [
            'releases' => $releases,
        ]);
    }

    public function edit(Request $request, Release $release)
    {
        $artist = $request->user()->artist()->firstOrFail();
        abort_unless($release->artist_id === $artist->id, 403);

        return Inertia::render('Artist/Releases/Edit', [
            'release' => $release,
        ]);
    }

    public function update(Request $request, Release $release)
    {
        $artist = $request->user()->artist()->firstOrFail();
        abort_unless($release->artist_id === $artist->id, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'release_date' => ['nullable', 'date'],
            // limitar plataformas si quieres
            'spotify_url' => ['nullable', 'url'],
            'youtube_url' => ['nullable', 'url'],
        ]);

        $this->releaseService->update($release, $data);

        return redirect()->route('artist.releases.index')->with('success', 'Lanzamiento actualizado');
    }
}
