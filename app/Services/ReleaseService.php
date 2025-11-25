<?php

namespace App\Services;

use App\Models\Release;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReleaseService
{
    public function getAll(int $perPage = 10)
    {
        return Release::with(['artist', 'tracks'])
            ->orderBy('release_date', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Release
    {
        Log::info('🎨 [ReleaseService] Creando release', ['keys' => array_keys($data)]);

        $data['slug'] = Str::slug($data['title']);

        // Subida de portada (cover_file) → ImageKit
        if (!empty($data['cover_file'])) {
            try {
                $imageKit = app(\App\Services\ImageKitService::class);
                $result = $imageKit->upload($data['cover_file'], '/releases');
                if ($result) {
                    $data['cover_url'] = $result['url'];
                    $data['cover_id']  = $result['file_id'];
                    Log::info('✅ [ReleaseService] Portada subida', ['url' => $result['url']]);
                } else {
                    Log::warning('⚠️ [ReleaseService] Falló upload de portada');
                }
            } catch (\Throwable $e) {
                Log::error('❌ [ReleaseService] Error subiendo portada', ['error' => $e->getMessage()]);
            }
            unset($data['cover_file']);
        }

        $release = Release::create($data);
        Log::info('✅ [ReleaseService] Release creado', ['id' => $release->id, 'title' => $release->title]);

        return $release->load(['artist', 'tracks']);
    }

    public function getByIdOrSlug(string|int $id): Release
    {
        return Release::where('id', $id)
            ->orWhere('slug', $id)
            ->with(['artist', 'tracks'])
            ->firstOrFail();
    }

    public function update(Release $release, array $data): Release
    {
        Log::info('✏️ [ReleaseService] Actualizando release', ['id' => $release->id]);

        if (!empty($data['cover_file'])) {
            try {
                $imageKit = app(\App\Services\ImageKitService::class);
                if ($release->cover_id) {
                    $imageKit->delete($release->cover_id);
                    Log::info('🗑️ [ReleaseService] Portada previa eliminada');
                }
                $result = $imageKit->upload($data['cover_file'], '/releases');
                if ($result) {
                    $data['cover_url'] = $result['url'];
                    $data['cover_id']  = $result['file_id'];
                    Log::info('✅ [ReleaseService] Nueva portada subida', ['url' => $result['url']]);
                } else {
                    Log::warning('⚠️ [ReleaseService] Falló upload de nueva portada');
                }
            } catch (\Throwable $e) {
                Log::error('❌ [ReleaseService] Error subiendo nueva portada', ['error' => $e->getMessage()]);
            }
            unset($data['cover_file']);
        }

        $release->update($data);
        Log::info('✅ [ReleaseService] Release actualizado');

        return $release->fresh(['artist', 'tracks']);
    }

    public function delete(Release $release): void
    {
        Log::warning('🗑️ [ReleaseService] Eliminando release', ['id' => $release->id]);
        $release->delete();
    }
}
