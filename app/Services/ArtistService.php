<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ArtistService
{
    /** Campos de imagen administrados por ImageKit */
    private const IMAGE_FIELDS = [
        'banner_home',
        'banner_artist',
        'carousel_home',
        'carousel_discography',
    ];

    /** TTL cache consultas públicas (min). 0 = sin cache */
    private int $publicTtl = 5;

    /* =========================
     |  CONSULTAS PÚBLICAS
     |=========================*/

    /**
     * Listar artistas (público) con soporte para incluir tracks
     * para las tarjetas tipo playlist.
     */
    public function getAll(
        int $perPage = 10,
        bool $withTracksForPlaylists = true,
        int $tracksLimit = 10
    ): LengthAwarePaginator {
        $key = "artists.index:p{$perPage}:t{$withTracksForPlaylists}:l{$tracksLimit}";

        $fetch = function () use ($perPage, $withTracksForPlaylists, $tracksLimit) {
            $query = Artist::query()
                ->select([
                    'artists.id',
                    'artists.name',
                    'artists.slug',
                    'artists.country',
                    'artists.bio',
                    'artists.genre_id',
                    'artists.banner_home_url',
                    'artists.banner_artist_url',
                    'artists.carousel_home_url',
                    'artists.carousel_discography_url',
                ])
                ->with('genre:id,name')
                ->withCount('releases')
                ->orderBy('artists.name');

            if ($withTracksForPlaylists) {
                $query->with(['tracks' => function ($q) use ($tracksLimit) {
                    $q->select([
                        'tracks.id',
                        'tracks.title',
                        'tracks.preview_url',
                        'tracks.duration',
                        'track_artist.artist_id',
                        'track_artist.track_id',
                    ])
                    ->orderBy('tracks.id', 'desc')
                    ->limit($tracksLimit);
                }]);
            }

            return $query->paginate($perPage);
        };

        return $this->publicTtl > 0
            ? Cache::remember($key, now()->addMinutes($this->publicTtl), $fetch)
            : $fetch();
    }

    /** Obtener artistas destacados (para home/carrusel compacto) */
    public function getFeatured(int $limit = 8)
    {
        $key = "artists.featured:l{$limit}";

        $fetch = function () use ($limit) {
            return Artist::query()
                ->select([
                    'artists.id',
                    'artists.name',
                    'artists.slug',
                    'artists.carousel_home_url',
                ])
                ->where('artists.is_featured', true)
                ->orderBy('artists.name')
                ->limit($limit)
                ->get();
        };

        return $this->publicTtl > 0
            ? Cache::remember($key, now()->addMinutes($this->publicTtl), $fetch)
            : $fetch();
    }

    /**
     * Detalle por id o slug (público).
     * Incluye releases y todos los tracks.
     */
    public function getByIdOrSlug(int|string $idOrSlug): Artist
    {
        $key = "artists.show:{$idOrSlug}";

        $fetch = function () use ($idOrSlug) {
            $query = Artist::query()
                ->withCount('releases')
                ->with([
                    'tracks' => function ($q) {
                        $q->select([
                            'tracks.id',
                            'tracks.title',
                            'tracks.preview_url',
                            'tracks.duration',
                            'track_artist.artist_id',
                            'track_artist.track_id',
                        ])
                        ->orderBy('tracks.id', 'desc');
                    },
                    'releases' => function ($q) {
                        $q->select([
                            'releases.id',
                            'releases.title',
                            'releases.slug',
                            'releases.artist_id',
                            'releases.cover_url',
                            'releases.release_date',
                            'releases.type',
                            'releases.description',
                            'releases.spotify_url',
                            'releases.youtube_url',
                            'releases.apple_music_url',
                            'releases.deezer_url',
                            'releases.amazon_music_url',
                            'releases.soundcloud_url',
                            'releases.tidal_url',
                        ])
                        ->orderBy('releases.release_date', 'desc');
                    },
                ]);

            // Si es un número, buscar por ID; si no, buscar por slug
            if (is_numeric($idOrSlug)) {
                $query->where('artists.id', (int)$idOrSlug);
            } else {
                $query->where('artists.slug', $idOrSlug);
            }

            return $query->firstOrFail();
        };

        return $this->publicTtl > 0
            ? Cache::remember($key, now()->addMinutes($this->publicTtl), $fetch)
            : $fetch();
    }

    /** Búsqueda simple por nombre (público) */
    public function search(string $term, int $perPage = 10): LengthAwarePaginator
    {
        $term = trim($term);

        return Artist::query()
            ->select([
                'artists.id',
                'artists.name',
                'artists.slug',
                'artists.carousel_home_url',
            ])
            ->where('artists.name', 'like', "%{$term}%")
            ->orderBy('artists.name')
            ->paginate($perPage);
    }

    /* =========================
     |  CRUD (ADMIN)
     |=========================*/

    /** Crear un nuevo artista */
    public function create(array $data): Artist
    {
        Log::info('🎨 [ArtistService] Iniciando creación de artista', [
            'data' => array_keys($data)
        ]);

        return DB::transaction(function () use ($data) {

            // slug
            $data['slug'] = Str::slug($data['name']);

            // crear usuario
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('artist');

            Log::info('✅ [ArtistService] Usuario creado', [
                'id' => $user->id,
                'email' => $user->email,
            ]);

            // limpiar campos que no van a artists
            unset($data['email'], $data['password']);

            // uploads
            $this->handleUploads($data);

            // crear artista 
            $artist = Artist::create([
                ...$data,
                'user_id' => $user->id,
            ]);

            Log::info('✅ [ArtistService] Artista creado', [
                'id' => $artist->id,
                'name' => $artist->name,
            ]);

            $this->flushPublicCaches();

            return $artist;
        });
    }


    /** Actualizar artista existente (acepta modelo o id) */
    public function update(Artist|int $artist, array $data): Artist
    {
        $artist = $artist instanceof Artist ? $artist : Artist::findOrFail($artist);
        Log::info('✏️ [ArtistService] Actualizando artista', ['id' => $artist->id]);

        if (array_key_exists('email', $data) || array_key_exists('password', $data) || array_key_exists('phone', $data)) {
            $user = $artist->user;
            if ($user) {
                if (!empty($data['email'])) {
                    $user->email = $data['email'];
                }
                if (array_key_exists('phone', $data)) {
                    $user->phone = $data['phone'];
                }
                if (!empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                }
                if ($user->isDirty()) {
                    $user->save();
                }
            }
            unset($data['email'], $data['password']);
        }

        // Si cambió el nombre, re-slug opcional
        if (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Manejo de imágenes (reemplazo y borrado previo)
        $this->handleUploads($data, $artist);

        $artist->update($data);
        Log::info('✅ [ArtistService] Artista actualizado', ['id' => $artist->id]);

        $this->flushPublicCaches();
        return $artist->fresh();
    }

    /** Eliminar una imagen específica de un artista */
    public function deleteImage(Artist|int $artist, string $fieldName): bool
    {
        $artist = $artist instanceof Artist ? $artist : Artist::findOrFail($artist);
        
        // Validar que el campo sea uno de los permitidos
        if (!in_array($fieldName, self::IMAGE_FIELDS)) {
            Log::warning('⚠️ [ArtistService] Campo de imagen inválido', ['field' => $fieldName]);
            return false;
        }

        $idKey = "{$fieldName}_id";
        $urlKey = "{$fieldName}_url";

        if (!$artist->$idKey) {
            Log::warning('⚠️ [ArtistService] La imagen no existe', ['artist_id' => $artist->id, 'field' => $fieldName]);
            return false;
        }

        try {
            $imageKit = app(\App\Services\ImageKitService::class);
            $imageKit->delete($artist->$idKey);
            
            // Limpiar datos de la BD
            $artist->update([
                $urlKey => null,
                $idKey => null,
            ]);

            Log::info('✅ [ArtistService] Imagen eliminada', ['artist_id' => $artist->id, 'field' => $fieldName]);
            $this->flushPublicCaches();
            return true;
        } catch (\Throwable $e) {
            Log::error('❌ [ArtistService] Error eliminando imagen', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** Eliminar artista (borra solo el registro; archivos quedan a criterio) */
    public function delete(Artist|int $artist): void
    {
        $artist = $artist instanceof Artist ? $artist : Artist::findOrFail($artist);
        Log::warning('🗑️ [ArtistService] Eliminando artista', ['id' => $artist->id]);

        // Si quieres borrar también en ImageKit, descomenta:
        // $this->deleteRemoteImages($artist);

        $artist->delete();
        $this->flushPublicCaches();
    }

    /* =========================
     |  AUXILIARES
     |=========================*/

    /**
     * Manejo centralizado de uploads a ImageKit.
     * - Crea: sube y setea *_url / *_id
     * - Update: elimina el archivo remoto previo si hay reemplazo
     */
    private function handleUploads(array &$data, ?Artist $existing = null): void
    {
        $imageKit = app(\App\Services\ImageKitService::class);

        foreach (self::IMAGE_FIELDS as $field) {
            $fileKey = "{$field}_file";
            $idKey   = "{$field}_id";
            $urlKey  = "{$field}_url";

            /** @var \Illuminate\Http\UploadedFile|null $file */
            $file = $data[$fileKey] ?? null;

            if ($file instanceof UploadedFile) {
                Log::info("🖼 [ArtistService] Procesando upload de $field", [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ]);

                try {
                    // Si estamos actualizando: borrar previo
                    if ($existing && $existing->$idKey) {
                        $imageKit->delete($existing->$idKey);
                        Log::info("🗑️ [ArtistService] Imagen previa $field eliminada", ['file_id' => $existing->$idKey]);
                    }

                    $result = $imageKit->upload($file, '/artists');
                    if ($result) {
                        $data[$urlKey] = $result['url'] ?? null;
                        $data[$idKey]  = $result['file_id'] ?? null;
                        Log::info("✅ [ArtistService] Imagen $field subida", ['url' => $data[$urlKey]]);
                    } else {
                        Log::warning("⚠️ [ArtistService] Upload $field devolvió resultado vacío");
                    }
                } catch (\Throwable $e) {
                    Log::error("❌ [ArtistService] Error subiendo $field", ['error' => $e->getMessage()]);
                }

                unset($data[$fileKey]);
            } else {
                // No llegó archivo: no tocar *_url ni *_id
                unset($data[$fileKey]);
            }
        }
    }

    /** Borrado remoto opcional de imágenes asociadas al artista */
    private function deleteRemoteImages(Artist $artist): void
    {
        $imageKit = app(\App\Services\ImageKitService::class);

        foreach (self::IMAGE_FIELDS as $field) {
            $idKey = "{$field}_id";
            if ($artist->$idKey) {
                try {
                    $imageKit->delete($artist->$idKey);
                    Log::info("🗑️ [ArtistService] Imagen $field eliminada en remoto", ['file_id' => $artist->$idKey]);
                } catch (\Throwable $e) {
                    Log::error("❌ [ArtistService] Error borrando $field remoto", ['error' => $e->getMessage()]);
                }
            }
        }
    }

    /** Limpiar caches públicas relacionadas con listados/detalles */
    private function flushPublicCaches(): void
    {
        if ($this->publicTtl <= 0) return;

        Cache::flush();
    }
}
