<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Services\GenreService;
use Inertia\Inertia;

class GenreController extends Controller
{
    protected GenreService $genreService;

    public function __construct(GenreService $genreService)
    {
        $this->genreService = $genreService;
    }

    /**
     * Mostrar lista de géneros (público)
     */
    public function index()
    {
        $genres = $this->genreService->getAll(12);

        return Inertia::render('Public/Genres/Index', [
            'genres' => $genres,
        ]);
    }

    /**
     * Mostrar detalle de un género con sus lanzamientos
     */
    public function show(int $id)
    {
        $genre = $this->genreService->getById($id);

        return Inertia::render('Public/Genres/Show', [
            'genre' => $genre,
        ]);
    }
}
