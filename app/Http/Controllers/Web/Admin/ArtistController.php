<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArtistRequest;
use App\Http\Requests\UpdateArtistRequest;
use App\Services\ArtistService;
use App\Models\{Artist, Genre};
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $artists = $this->artistService->getAll(10);

        return Inertia::render('Admin/Artists/Index', [
            'artists' => $artists,
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
            'artist' => $artist->load('genre', 'releases'),
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
