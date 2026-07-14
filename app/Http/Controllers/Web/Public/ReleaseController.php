<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicCatalog\PublicReleaseCatalogService;
use Inertia\Inertia;

class ReleaseController extends Controller
{
    protected PublicReleaseCatalogService $releaseCatalogService;

    public function __construct(PublicReleaseCatalogService $releaseCatalogService)
    {
        $this->releaseCatalogService = $releaseCatalogService;
    }

    /**
     * Mostrar lista de lanzamientos (público)
     */
    public function index()
    {
        $releases = $this->releaseCatalogService->paginatedReleases(12);

        return Inertia::render('Public/Releases/Index', [
            'releases' => $releases,
        ]);
    }

    /**
     * Mostrar detalle de un lanzamiento (por slug o id)
     */
    public function show(string $slug)
    {
        $release = $this->releaseCatalogService->releaseBySlug($slug);

        return Inertia::render('Public/Releases/Show', [
            'release' => $release,
        ]);
    }
}
