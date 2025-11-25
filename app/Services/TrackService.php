<?php

namespace App\Services;

use App\Models\Track;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TrackService
{
    /**
     * Listar todos los tracks (paginados)
     */
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return Track::with(['release.artist', 'artists'])
            ->orderBy('release_id')
            ->orderBy('track_number')
            ->paginate($perPage);
    }

    /**
     * Crear un nuevo track
     */
    public function create(array $data): Track
    {
        // Manejar archivo de portada de la pista
        if (!empty($data['cover_file'])) {
            $imageKit = app(\App\Services\ImageKitService::class);
            $result = $imageKit->upload($data['cover_file'], '/tracks');
            if ($result) {
                $data['cover_url'] = $result['url'];
                $data['cover_id'] = $result['file_id'];
            }
            unset($data['cover_file']);
        }

        $track = Track::create($data);

        // Si se envían artistas relacionados, sincronizar
        if (!empty($data['artist_ids'])) {
            $track->artists()->sync($data['artist_ids']);
        }

        return $track->load(['release.artist', 'artists']);
    }

    /**
     * Obtener un track por ID
     */
    public function getById(int $id): Track
    {
        return Track::with(['release.artist', 'artists'])
            ->findOrFail($id);
    }

    /**
     * Actualizar un track existente
     */
    public function update(Track $track, array $data): Track
    {
        if (!empty($data['cover_file'])) {
            $imageKit = app(\App\Services\ImageKitService::class);
            if ($track->cover_id) {
                $imageKit->delete($track->cover_id);
            }
            $result = $imageKit->upload($data['cover_file'], '/tracks');
            if ($result) {
                $data['cover_url'] = $result['url'];
                $data['cover_id'] = $result['file_id'];
            }
            unset($data['cover_file']);
        }

        $track->update($data);

        // Actualizar artistas si se envían
        if (isset($data['artist_ids'])) {
            $track->artists()->sync($data['artist_ids']);
        }

        return $track->fresh(['release.artist', 'artists']);
    }

    /**
     * Eliminar un track
     */
    public function delete(Track $track): void
    {
        $track->artists()->detach(); // limpiar relaciones pivot
        $track->delete();
    }
}
