<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Services\ReleaseService;
use Inertia\Inertia;

class ReleaseController extends Controller
{
    protected ReleaseService $releaseService;

    public function __construct(ReleaseService $releaseService)
    {
        $this->releaseService = $releaseService;
    }

    /**
     * Mostrar lista de lanzamientos (público)
     */
    public function index()
    {
        $releases = $this->releaseService->getAll(12);

        return Inertia::render('Public/Releases/Index', [
            'releases' => $releases,
        ]);
    }

    /**
     * Mostrar detalle de un lanzamiento (por slug o id)
     */
    public function show(string $slug)
    {
        $release = $this->releaseService->getByIdOrSlug($slug);

        return Inertia::render('Public/Releases/Show', [
            'release' => $release,
        ]);
    }
}
