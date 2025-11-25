<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Services\TrackService;
use Inertia\Inertia;

class TrackController extends Controller
{
    protected TrackService $trackService;

    public function __construct(TrackService $trackService)
    {
        $this->trackService = $trackService;
    }

    /**
     * Mostrar lista de pistas (público)
     */
    public function index()
    {
        $tracks = $this->trackService->getAll(20);

        return Inertia::render('Public/Tracks/Index', [
            'tracks' => $tracks,
        ]);
    }

    /**
     * Mostrar detalle de una pista (por id o slug si se define)
     */
    public function show(int $id)
    {
        $track = $this->trackService->getById($id);

        return Inertia::render('Public/Tracks/Show', [
            'track' => $track,
        ]);
    }
}
