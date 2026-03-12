<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArtistRequest;
use App\Http\Requests\StoreExternalArtistInvitationRequest;
use App\Http\Requests\UpdateArtistRequest;
use App\Models\{Artist, Event, Genre, Release, User};
use App\Services\ArtistService;
use App\Services\ExternalArtistInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ArtistController extends Controller
{
    protected ArtistService $artistService;

    public function __construct(ArtistService $artistService)
    {
        $this->artistService = $artistService;
    }

    /** Listado de artistas */
    public function index(Request $request)
    {
        $artists = Artist::query()
            ->select([
                'artists.id',
                'artists.name',
                'artists.country',
                'artists.bio',
                'artists.phone',
                'artists.genre_id',
                'artists.banner_artist_url',
                'artists.carousel_home_url',
            ])
            ->with('genre:id,name')
            ->with('user:id,name,stage_name,email,phone,identification_type,identification_number,additional_information')
            ->withCount('releases')
            ->orderBy('artists.name')
            ->paginate(10)
            ->withQueryString();

        $externalArtists = User::query()
            ->role('external_artist')
            ->select([
                'users.id',
                'users.name',
                'users.stage_name',
                'users.email',
                'users.phone',
                'users.identification_type',
                'users.identification_number',
                'users.additional_information',
            ])
            ->orderByRaw("COALESCE(NULLIF(stage_name, ''), name)")
            ->get();

        return Inertia::render('Admin/Artists/Index', [
            'artists' => $artists,
            'externalArtists' => $externalArtists,
        ]);
    }

    /** Formulario de creación */
    public function create()
    {
        $genres = Genre::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Artists/Create', [
            'genres' => $genres,
        ]);
    }

    /** Guardar artista */
    public function store(StoreArtistRequest $request)
    {
        // ✅ Combinar datos validados + archivos
        $data = $request->validated();

        foreach (['banner_home', 'banner_artist', 'carousel_home', 'carousel_discography'] as $field) {
            // soporta tanto banner_home como banner_home_file
            if ($request->hasFile($field)) {
                $data["{$field}_file"] = $request->file($field);
            } elseif ($request->hasFile("{$field}_file")) {
                $data["{$field}_file"] = $request->file("{$field}_file");
            }
        }

        Log::info('🎯 [ArtistController] Datos enviados al servicio', [
            'keys' => array_keys($data),
            'files' => collect($data)->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)->keys()->toArray(),
        ]);

        $this->artistService->create($data);

        return redirect()
            ->route('admin.artists.index')
            ->with('success', 'Artista creado correctamente');
    }

    /** Formulario de edición */
    public function edit(Artist $artist)
    {
        $genres = Genre::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Artists/Edit', [
            'artist' => $artist->load('genre', 'releases', 'user'),
            'genres' => $genres,
        ]);
    }

    /** Actualizar artista */
    public function update(UpdateArtistRequest $request, Artist $artist)
    {
        $data = $request->validated();

        foreach (['banner_home', 'banner_artist', 'carousel_home', 'carousel_discography'] as $field) {
            if ($request->hasFile($field)) {
                $data["{$field}_file"] = $request->file($field);
            } elseif ($request->hasFile("{$field}_file")) {
                $data["{$field}_file"] = $request->file("{$field}_file");
            }
        }

        Log::info('✏️ [ArtistController] Datos enviados al servicio (update)', [
            'artist_id' => $artist->id,
            'files' => collect($data)->filter(fn($v) => $v instanceof \Illuminate\Http\UploadedFile)->keys()->toArray(),
        ]);

        $this->artistService->update($artist, $data);

        return redirect()
            ->route('admin.artists.index')
            ->with('success', 'Artista actualizado correctamente');
    }

    /** Eliminar artista */
    public function destroy(Artist $artist)
    {
        $this->artistService->delete($artist);

        return redirect()
            ->route('admin.artists.index')
            ->with('success', 'Artista eliminado correctamente');
    }

    /** Invitar artista externo desde módulo de artistas */
    public function inviteExternalArtist(
        StoreExternalArtistInvitationRequest $request,
        ExternalArtistInvitationService $invitationService
    ) {
        $payload = $request->validated();

        $invitationService->inviteStandalone(
            inviteeName: $payload['name'],
            email: $payload['email'],
            inviter: $request->user()
        );

        return redirect()
            ->route('admin.artists.index')
            ->with('success', 'Invitación enviada correctamente al artista externo.');
    }

    /** Papelera de artistas */
    public function trash(Request $request)
    {
        Gate::authorize('trash.view.content');

        $artists = Artist::onlyTrashed()
            ->select('id', 'name', 'slug', 'deleted_at')
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->through(function (Artist $artist): array {
                $activeReleases = Release::query()
                    ->where('artist_id', $artist->id)
                    ->count();

                $activeMainEvents = Event::query()
                    ->where('main_artist_id', $artist->id)
                    ->count();

                $blockedReason = null;
                if ($activeReleases > 0) {
                    $blockedReason = "Tiene {$activeReleases} lanzamientos activos.";
                } elseif ($activeMainEvents > 0) {
                    $blockedReason = "Tiene {$activeMainEvents} eventos activos como artista principal.";
                }

                return [
                    'id' => $artist->id,
                    'primary' => $artist->name,
                    'secondary' => $artist->slug,
                    'deleted_at' => $artist->deleted_at,
                    'can_force_delete' => $blockedReason === null,
                    'force_delete_blocked_reason' => $blockedReason,
                ];
            });

        if ($request->expectsJson()) {
            return response()->json($artists);
        }

        return Inertia::render('Admin/Trash/Index', [
            'title' => 'Papelera · Artistas',
            'items' => $artists,
            'restoreRoute' => 'admin.artists.restore',
            'forceDeleteRoute' => 'admin.artists.force-delete',
            'backRoute' => 'admin.artists.index',
        ]);
    }

    /** Restaurar artista */
    public function restore(int $artistId)
    {
        Gate::authorize('trash.manage.content');

        $artist = Artist::onlyTrashed()->findOrFail($artistId);
        $relatedUser = $artist->user_id ? User::withTrashed()->find($artist->user_id) : null;
        if ($artist->user_id && (!$relatedUser || $relatedUser->trashed())) {
            return back()->withErrors([
                'artist' => 'No se puede restaurar el artista porque su usuario asociado está eliminado. Restaura primero ese usuario.',
            ]);
        }

        $relatedGenre = $artist->genre_id ? Genre::withTrashed()->find($artist->genre_id) : null;
        if ($artist->genre_id && (!$relatedGenre || $relatedGenre->trashed())) {
            return back()->withErrors([
                'artist' => 'No se puede restaurar el artista porque su género asociado está eliminado. Restaura primero ese género.',
            ]);
        }

        DB::transaction(function () use ($artist): void {
            $artist->restore();
        });

        return redirect()
            ->route('admin.artists.index')
            ->with('success', 'Artista restaurado correctamente');
    }

    /** Eliminar artista de forma permanente */
    public function forceDelete(int $artistId)
    {
        Gate::authorize('trash.manage.content');

        $artist = Artist::withTrashed()->findOrFail($artistId);

        if (!$artist->trashed()) {
            return back()->withErrors([
                'artist' => 'Solo puedes eliminar permanentemente artistas en papelera.',
            ]);
        }

        $activeReleases = Release::query()
            ->where('artist_id', $artist->id)
            ->count();

        if ($activeReleases > 0) {
            return back()->withErrors([
                'artist' => "No se puede eliminar permanentemente el artista: tiene {$activeReleases} lanzamientos activos.",
            ]);
        }

        $activeMainEvents = Event::query()
            ->where('main_artist_id', $artist->id)
            ->count();

        if ($activeMainEvents > 0) {
            return back()->withErrors([
                'artist' => "No se puede eliminar permanentemente el artista: tiene {$activeMainEvents} eventos activos como artista principal.",
            ]);
        }

        DB::transaction(function () use ($artist): void {
            $artist->tracks()->detach();
            $artist->events()->detach();
            $artist->forceDelete();
        });

        return redirect()
            ->route('admin.artists.index')
            ->with('success', 'Artista eliminado permanentemente');
    }

    /** Eliminar una imagen específica del artista (AJAX) */
    public function deleteImage(Request $request, Artist $artist)
    {
        $fieldName = $request->input('field');

        if (!$fieldName) {
            return response()->json(['error' => 'Campo requerido'], 400);
        }

        $success = $this->artistService->deleteImage($artist, $fieldName);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
                'artist' => $artist->fresh(),
            ]);
        }

        return response()->json(['error' => 'No se pudo eliminar la imagen'], 500);
    }
}
