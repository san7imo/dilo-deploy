<?php

namespace App\Services;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class GenreService
{
    /**
     * Listar todos los géneros (paginados)
     */
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return Genre::with('releases')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Crear un nuevo género
     */
    public function create(array $data): Genre
    {
        $data['slug'] = Str::slug($data['name']);
        return Genre::create($data);
    }

    /**
     * Obtener un género por ID
     */
    public function getById(int $id): Genre
    {
        return Genre::with('releases')->findOrFail($id);
    }

    /**
     * Actualizar un género
     */
    public function update(Genre $genre, array $data): Genre
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $genre->update($data);
        return $genre->fresh('releases');
    }

    /**
     * Eliminar un género
     */
    public function delete(Genre $genre): void
    {
        $genre->releases()->detach(); // limpia la relación pivote
        $genre->delete();
    }
}
