<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackRequest;
use App\Http\Requests\UpdateTrackRequest;
use App\Services\TrackService;
use App\Models\{Track, Release, Artist};
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackController extends Controller
{
    protected TrackService $trackService;

    public function __construct(TrackService $trackService)
    {
        $this->trackService = $trackService;
    }

    /** Listado de pistas */
    public function index(Request $request)
    {
        $tracks = $this->trackService->getAll(10);

        return Inertia::render('Admin/Tracks/Index', [
            'tracks' => $tracks,
        ]);
    }

    /** Formulario de creación */
    public function create()
    {
        return Inertia::render('Admin/Tracks/Create', [
            'releases' => Release::orderBy('release_date', 'desc')->get(['id', 'title']),
            'artists'  => Artist::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Guardar nueva pista */
    public function store(StoreTrackRequest $request)
    {
        $data = $request->validated();

        // Manejo de archivo de portada
        if ($request->hasFile('cover')) {
            $data['cover_file'] = $request->file('cover');
        } elseif ($request->hasFile('cover_file')) {
            $data['cover_file'] = $request->file('cover_file');
        }

        Log::info('🎵 [TrackController] Datos enviados al servicio', [
            'keys' => array_keys($data),
            'files' => collect($data)->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)->keys()->toArray(),
        ]);

        $this->trackService->create($data);

        return redirect()
            ->route('admin.tracks.index')
            ->with('success', 'Pista creada correctamente');
    }

    /** Formulario de edición */
    public function edit(Track $track)
    {
        return Inertia::render('Admin/Tracks/Edit', [
            'track'    => $track->load(['release', 'artists']),
            'releases' => Release::orderBy('release_date', 'desc')->get(['id', 'title']),
            'artists'  => Artist::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Actualizar pista */
    public function update(UpdateTrackRequest $request, Track $track)
    {
        $data = $request->validated();

        if ($request->hasFile('cover')) {
            $data['cover_file'] = $request->file('cover');
        } elseif ($request->hasFile('cover_file')) {
            $data['cover_file'] = $request->file('cover_file');
        }

        Log::info('✏️ [TrackController] Datos enviados al servicio (update)', [
            'track_id' => $track->id,
            'files' => collect($data)->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)->keys()->toArray(),
        ]);

        $this->trackService->update($track, $data);

        return redirect()
            ->route('admin.tracks.index')
            ->with('success', 'Pista actualizada correctamente');
    }

    /** Eliminar pista */
    public function destroy(Track $track)
    {
        $this->trackService->delete($track);

        return redirect()
            ->route('admin.tracks.index')
            ->with('success', 'Pista eliminada correctamente');
    }
}
