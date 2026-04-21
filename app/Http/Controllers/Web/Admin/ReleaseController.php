<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReleaseRequest;
use App\Http\Requests\UpdateReleaseRequest;
use App\Models\{Artist, Genre, Release, Track};
use App\Services\ArtistCatalogService;
use App\Services\ReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReleaseController extends Controller
{
    protected ReleaseService $releaseService;
    protected ArtistCatalogService $artistCatalogService;

    public function __construct(ReleaseService $releaseService, ArtistCatalogService $artistCatalogService)
    {
        $this->releaseService = $releaseService;
        $this->artistCatalogService = $artistCatalogService;
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
        $this->artistCatalogService->syncExternalUsersWithoutArtist();

        return Inertia::render('Admin/Releases/Create', [
            'artists' => Artist::orderBy('name')->get(['id', 'name', 'artist_origin']),
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
        $this->artistCatalogService->syncExternalUsersWithoutArtist();

        return Inertia::render('Admin/Releases/Edit', [
            'release' => $release->load(['artist', 'tracks']),
            'artists' => Artist::orderBy('name')->get(['id', 'name', 'artist_origin']),
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

    public function trash(Request $request)
    {
        Gate::authorize('trash.view.content');

        $releases = Release::onlyTrashed()
            ->with('artist:id,name')
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(function (Release $release): array {
                $activeTracks = Track::query()
                    ->where('release_id', $release->id)
                    ->count();

                return [
                    'id' => $release->id,
                    'primary' => $release->title,
                    'secondary' => $release->artist?->name,
                    'deleted_at' => $release->deleted_at,
                    'can_force_delete' => $activeTracks === 0,
                    'force_delete_blocked_reason' => $activeTracks > 0
                        ? "Tiene {$activeTracks} pistas activas."
                        : null,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($releases);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Lanzamientos',
            'items' => $releases,
            'restoreRoute' => 'admin.releases.restore',
            'forceDeleteRoute' => 'admin.releases.force-delete',
            'backRoute' => 'admin.releases.index',
        ]);
    }

    public function restore(int $releaseId)
    {
        Gate::authorize('trash.manage.content');

        $release = Release::onlyTrashed()->findOrFail($releaseId);
        $artist = Artist::withTrashed()->find($release->artist_id);
        if (!$artist || $artist->trashed()) {
            return back()->withErrors([
                'release' => 'No se puede restaurar el lanzamiento porque su artista está eliminado. Restaura primero el artista.',
            ]);
        }

        DB::transaction(function () use ($release): void {
            $release->restore();

            Track::onlyTrashed()
                ->where('release_id', $release->id)
                ->restore();
        });

        return redirect()
            ->route('admin.releases.index')
            ->with('success', 'Lanzamiento restaurado correctamente');
    }

    public function forceDelete(int $releaseId)
    {
        Gate::authorize('trash.manage.content');

        $release = Release::withTrashed()->findOrFail($releaseId);

        if (!$release->trashed()) {
            return back()->withErrors([
                'release' => 'Solo puedes eliminar permanentemente lanzamientos en papelera.',
            ]);
        }

        $activeTracks = Track::query()
            ->where('release_id', $release->id)
            ->count();

        if ($activeTracks > 0) {
            return back()->withErrors([
                'release' => "No se puede eliminar permanentemente el lanzamiento: tiene {$activeTracks} pistas activas.",
            ]);
        }

        DB::transaction(function () use ($release): void {
            Track::onlyTrashed()
                ->where('release_id', $release->id)
                ->get()
                ->each
                ->forceDelete();

            $release->forceDelete();
        });

        return redirect()
            ->route('admin.releases.index')
            ->with('success', 'Lanzamiento eliminado permanentemente');
    }
}
