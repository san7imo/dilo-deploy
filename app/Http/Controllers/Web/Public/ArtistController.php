<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Services\ArtistService;
use Inertia\Inertia;

class ArtistController extends Controller
{
    protected ArtistService $artistService;

    public function __construct(ArtistService $artistService)
    {
        $this->artistService = $artistService;
    }

    /**
     * Mostrar lista de artistas (público)
     */
    public function index()
    {
        $artists = $this->artistService->getAll(12);

        return Inertia::render('Public/Artists/Index', [
            'artists' => $artists,
            'banner' => [
                'title' => 'Conéctate con el talento de nuestros',
                'highlight' => 'ARTISTAS',
                'cta' => 'Mira ahora',
                'image' => asset('videos/artists-hero.mp4'),
            ],
        ]);
    }

    /**
     * Mostrar detalle de un artista
     */
    public function show(string $slug)
    {
        $artist = $this->artistService->getByIdOrSlug($slug);

        return Inertia::render('Public/Artists/Show', [
            'artist' => $artist,
        ]);
    }
}
