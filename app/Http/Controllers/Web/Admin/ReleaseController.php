<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReleaseRequest;
use App\Http\Requests\UpdateReleaseRequest;
use App\Services\ReleaseService;
use App\Models\{Release, Artist, Genre};
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReleaseController extends Controller
{
    protected ReleaseService $releaseService;

    public function __construct(ReleaseService $releaseService)
    {
        $this->releaseService = $releaseService;
    }

    /** 📜 Listado de lanzamientos (panel admin) */
    public function index(Request $request)
    {
        $releases = $this->releaseService->getAll(10);

        return Inertia::render('Admin/Releases/Index', [
            'releases' => $releases,
        ]);
    }

    /** 🆕 Formulario de creación */
    public function create()
    {
        return Inertia::render('Admin/Releases/Create', [
            'artists' => Artist::orderBy('name')->get(['id', 'name']),
            'genres'  => Genre::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** 💾 Guardar nuevo release */
    public function store(StoreReleaseRequest $request)
    {
        // ✅ Combinar datos validados + archivos
        $data = $request->validated();

        if ($request->hasFile('cover')) {
            $data['cover_file'] = $request->file('cover');
        } elseif ($request->hasFile('cover_file')) {
            $data['cover_file'] = $request->file('cover_file');
        }

        Log::info('🎯 [ReleaseController] Datos enviados al servicio (store)', [
            'keys'  => array_keys($data),
            'files' => collect($data)
                ->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)
                ->keys()
                ->toArray(),
        ]);

        $this->releaseService->create($data);

        return redirect()
            ->route('admin.releases.index')
            ->with('success', 'Lanzamiento creado correctamente');
    }

    /** ✏️ Formulario de edición */
    public function edit(Release $release)
    {
        return Inertia::render('Admin/Releases/Edit', [
            'release' => $release->load(['artist', 'tracks']),
            'artists' => Artist::orderBy('name')->get(['id', 'name']),
            'genres'  => Genre::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** 🔁 Actualizar release */
    public function update(UpdateReleaseRequest $request, Release $release)
    {
        $data = $request->validated();

        if ($request->hasFile('cover')) {
            $data['cover_file'] = $request->file('cover');
        } elseif ($request->hasFile('cover_file')) {
            $data['cover_file'] = $request->file('cover_file');
        }

        Log::info('✏️ [ReleaseController] Datos enviados al servicio (update)', [
            'release_id' => $release->id,
            'files' => collect($data)
                ->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)
                ->keys()
                ->toArray(),
        ]);

        $this->releaseService->update($release, $data);

        return redirect()
            ->route('admin.releases.index')
            ->with('success', 'Lanzamiento actualizado correctamente');
    }

    /** 🗑️ Eliminar release */
    public function destroy(Release $release)
    {
        $this->releaseService->delete($release);

        return redirect()
            ->route('admin.releases.index')
            ->with('success', 'Lanzamiento eliminado correctamente');
    }
}
