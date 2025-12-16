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
        $artist = $user->artist()
            ->select('id', 'name', 'bio', 'country', 'genre_id')
            ->with('genre:id,name')
            ->firstOrFail();

        return response()->json(['artist' => $artist]);
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
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
        ]);

        // Limitar campos que el artista puede modificar
        $artist->update($data);

        return redirect()->route('artist.profile.edit')->with('success', 'Perfil actualizado');
    }
}
