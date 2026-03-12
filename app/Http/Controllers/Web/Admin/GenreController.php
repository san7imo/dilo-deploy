<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Artist;
use App\Models\Genre;
use App\Services\GenreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

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

    public function trash(Request $request)
    {
        Gate::authorize('trash.view.content');

        $genres = Genre::onlyTrashed()
            ->select('id', 'name', 'slug', 'deleted_at')
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(function (Genre $genre): array {
                $activeArtists = Artist::query()
                    ->where('genre_id', $genre->id)
                    ->count();

                $activeReleases = DB::table('genre_release')
                    ->join('releases', 'releases.id', '=', 'genre_release.release_id')
                    ->where('genre_release.genre_id', $genre->id)
                    ->whereNull('releases.deleted_at')
                    ->count();

                $blockedReason = null;
                if ($activeArtists > 0) {
                    $blockedReason = "Tiene {$activeArtists} artistas activos asociados.";
                } elseif ($activeReleases > 0) {
                    $blockedReason = "Tiene {$activeReleases} lanzamientos activos asociados.";
                }

                return [
                    'id' => $genre->id,
                    'primary' => $genre->name,
                    'secondary' => $genre->slug,
                    'deleted_at' => $genre->deleted_at,
                    'can_force_delete' => $blockedReason === null,
                    'force_delete_blocked_reason' => $blockedReason,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($genres);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Géneros',
            'items' => $genres,
            'restoreRoute' => 'admin.genres.restore',
            'forceDeleteRoute' => 'admin.genres.force-delete',
            'backRoute' => 'admin.genres.index',
        ]);
    }

    public function restore(int $genreId)
    {
        Gate::authorize('trash.manage.content');

        $genre = Genre::onlyTrashed()->findOrFail($genreId);

        DB::transaction(function () use ($genre): void {
            $genre->restore();
        });

        return redirect()->route('admin.genres.index')
            ->with('success', 'Género restaurado correctamente');
    }

    public function forceDelete(int $genreId)
    {
        Gate::authorize('trash.manage.content');

        $genre = Genre::withTrashed()->findOrFail($genreId);

        if (!$genre->trashed()) {
            return back()->withErrors([
                'genre' => 'Solo puedes eliminar permanentemente géneros en papelera.',
            ]);
        }

        $activeArtists = Artist::query()
            ->where('genre_id', $genre->id)
            ->count();

        if ($activeArtists > 0) {
            return back()->withErrors([
                'genre' => "No se puede eliminar permanentemente el género: tiene {$activeArtists} artistas activos asociados.",
            ]);
        }

        $activeReleases = DB::table('genre_release')
            ->join('releases', 'releases.id', '=', 'genre_release.release_id')
            ->where('genre_release.genre_id', $genre->id)
            ->whereNull('releases.deleted_at')
            ->count();

        if ($activeReleases > 0) {
            return back()->withErrors([
                'genre' => "No se puede eliminar permanentemente el género: tiene {$activeReleases} lanzamientos activos asociados.",
            ]);
        }

        DB::transaction(function () use ($genre): void {
            $genre->releases()->detach();
            $genre->forceDelete();
        });

        return redirect()->route('admin.genres.index')
            ->with('success', 'Género eliminado permanentemente');
    }
}
