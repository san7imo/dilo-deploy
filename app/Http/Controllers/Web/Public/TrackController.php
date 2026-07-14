<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicCatalog\PublicMusicCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrackController extends Controller
{
    protected PublicMusicCatalogService $musicCatalogService;

    public function __construct(PublicMusicCatalogService $musicCatalogService)
    {
        $this->musicCatalogService = $musicCatalogService;
    }

    /**
     * Mostrar lista de pistas (público)
     */
    public function index(Request $request)
    {
        $tracks = $this->musicCatalogService->paginatedTracks(18, [
            'artist' => $request->string('artist')->toString(),
            'release' => $request->string('release')->toString(),
            'search' => $request->string('search')->toString(),
        ]);

        return Inertia::render('Public/Songs/Index', [
            'tracks' => $tracks,
            'filters' => [
                'artist' => $request->string('artist')->toString(),
                'release' => $request->string('release')->toString(),
                'search' => $request->string('search')->toString(),
            ],
            'filterOptions' => $this->musicCatalogService->filterOptions(),
        ]);
    }

    /**
     * Mostrar detalle de una pista (por id o slug si se define)
     */
    public function show(int $id)
    {
        $track = $this->musicCatalogService->trackById($id);

        return Inertia::render('Public/Tracks/Show', [
            'track' => $track,
        ]);
    }
}
