<?php

namespace App\Http\Controllers\Web\Artist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function showData()
    {
        $user = Auth::user();
        $artistQuery = $user->artist()->with('genre:id,name')
            ->withCount(['tracks'])
            ->select(
                'id', 'name', 'bio', 'country', 'phone', 'genre_id',
                'banner_home_url', 'banner_artist_url', 'carousel_home_url', 'carousel_discography_url',
                'social_links'
            );

        $artist = $artistQuery->firstOrFail();

        // Releases list
        $releases = $artist->releases()
            ->select('id', 'title', 'slug', 'release_date', 'cover_url', 'description')
            ->orderByDesc('release_date')
            ->get();

        // Build payload
        $payload = [
            'id' => $artist->id,
            'name' => $artist->name,
            'bio' => $artist->bio,
            'country' => $artist->country,
            'phone' => $artist->phone,
            'genre' => $artist->genre ? ['id' => $artist->genre->id, 'name' => $artist->genre->name] : null,
            'image_url' => $artist->image_url ?? null,
            'main_image_url' => $artist->main_image_url ?? null,
            'social_links_formatted' => $artist->social_links_formatted ?? [],
            'tracks_count' => $artist->tracks_count ?? 0,
        ];

        return response()->json(['artist' => $payload, 'releases' => $releases]);
    }

    public function edit(Request $request)
    {
        $artist = $request->user()->artist()
            ->with('genre:id,name')
            ->firstOrFail();

        return Inertia::render('Artist/Profile/Edit', [
            'artist' => [
                'id' => $artist->id,
                'name' => $artist->name,
                'bio' => $artist->bio,
                'country' => $artist->country,
                'phone' => $artist->phone,
                'genre' => $artist->genre ? ['id' => $artist->genre->id, 'name' => $artist->genre->name] : null,
                'genre_id' => $artist->genre_id,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $artist = $request->user()->artist()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
        ]);

        // Limitar campos que el artista puede modificar
        $artist->update($data);

        if (array_key_exists('phone', $data)) {
            $request->user()->update(['phone' => $data['phone']]);
        }

        return redirect()->route('artist.profile.edit')->with('success', 'Perfil actualizado');
    }

    public function show()
    {
        // Render the Inertia show page; the page will fetch actual data via the profile.data endpoint
        return Inertia::render('Artist/Profile/Show');
    }
}
