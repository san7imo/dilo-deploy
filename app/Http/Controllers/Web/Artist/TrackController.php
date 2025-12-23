<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use App\Models\Track;
use Inertia\Inertia;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function index(Request $request)
    {
        $artist = $request->user()->artist()->firstOrFail();

        $tracks = Track::query()
            ->select('tracks.id', 'tracks.title', 'tracks.release_id')
            ->whereHas('artists', fn($q) => $q->where('artists.id', $artist->id))
            ->with(['release:id,title'])
            ->paginate(10);

        return Inertia::render('Artist/Tracks/Index', [
            'tracks' => $tracks,
        ]);
    }

    public function edit(Request $request, Track $track)
    {
        $artist = $request->user()->artist()->firstOrFail();

        abort_unless(
            $track->artists()->where('artists.id', $artist->id)->exists(),
            403
        );

        return Inertia::render('Artist/Tracks/Edit', [
            'track' => $track->load('release:id,title'),
        ]);
    }

    public function update(Request $request, Track $track)
    {
        $artist = $request->user()->artist()->firstOrFail();

        abort_unless(
            $track->artists()->where('artists.id', $artist->id)->exists(),
            403
        );

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            // campos permitidos al artista
            'spotify_url' => ['nullable', 'url'],
            'youtube_url' => ['nullable', 'url'],
        ]);

        $track->update($data);

        return redirect()->route('artist.tracks.index')->with('success', 'Pista actualizada');
    }
}
