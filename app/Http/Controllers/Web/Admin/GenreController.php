<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Services\GenreService;
use App\Models\Genre;
use Inertia\Inertia;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    protected GenreService $genreService;

    public function __construct(GenreService $genreService)
    {
        $this->genreService = $genreService;
    }

    public function index(Request $request)
    {
        $genres = $this->genreService->getAll(10);

        return Inertia::render('Admin/Genres/Index', [
            'genres' => $genres,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Genres/Create');
    }

    public function store(StoreGenreRequest $request)
    {
        $this->genreService->create($request->validated());

        return redirect()->route('admin.genres.index')
            ->with('success', 'Género creado correctamente');
    }

    public function edit(Genre $genre)
    {
        return Inertia::render('Admin/Genres/Edit', [
            'genre' => $genre->load('releases'),
        ]);
    }

    public function update(UpdateGenreRequest $request, Genre $genre)
    {
        $this->genreService->update($genre, $request->validated());

        return redirect()->route('admin.genres.index')
            ->with('success', 'Género actualizado correctamente');
    }

    public function destroy(Genre $genre)
    {
        $this->genreService->delete($genre);

        return redirect()->route('admin.genres.index')
            ->with('success', 'Género eliminado correctamente');
    }
}
