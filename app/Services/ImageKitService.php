<?php

namespace App\Services;

use ImageKit\ImageKit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageKitService
{
    protected $imageKit;

    public function __construct()
    {
        $this->imageKit = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.url_endpoint')
        );
    }

    /**
     * Sube una imagen a ImageKit y la convierte a WebP para SEO
     */
    public function upload($file, $folder = '/uploads', $customName = null)
    {
        try {
            // Determinar nombre de archivo
            $fileName = $customName ?? Str::random(10) . '.webp';

            // Aceptar UploadedFile u otros tipos (ruta)
            $filePath = null;
            if (is_object($file) && method_exists($file, 'getRealPath')) {
                $filePath = $file->getRealPath();
            } elseif (is_string($file) && file_exists($file)) {
                $filePath = $file;
            }

            if ($filePath && file_exists($filePath)) {
                // Convertir archivo a base64
                $base64File = base64_encode(file_get_contents($filePath));
            } elseif (is_string($file)) {
                // Si nos pasan directamente el contenido en base64
                $base64File = $file;
            } else {
                Log::error('Invalid file provided to ImageKitService::upload');
                return null;
            }

            $response = $this->imageKit->uploadFile([
                'file' => $base64File,
                'fileName' => $fileName,
                'folder' => $folder,
                'useUniqueFileName' => true,
                'tags' => implode(',', ['uploaded', 'laravel']),
                'isPrivateFile' => false,
                'responseFields' => 'tags,customMetadata',
                'transformation' => [
                    'pre' => 'f-webp,q-80',
                ],
            ]);

            if (!empty($response->error)) {
                Log::error('ImageKit upload error', ['error' => $response->error]);
                return null;
            }

            return [
                'url' => $response->result->url,
                'thumbnail' => $response->result->thumbnailUrl ?? null,
                'file_id' => $response->result->fileId,
                'name' => $response->result->name,
            ];
        } catch (\Throwable $e) {
            Log::error('Error subiendo imagen a ImageKit', ['exception' => $e]);
            return null;
        }
    }

    /**
     * Elimina un archivo por su ID en ImageKit
     */
    public function delete($fileId)
    {
        try {
            $response = $this->imageKit->deleteFile($fileId);
            return empty($response->error);
        } catch (\Throwable $e) {
            Log::error('Error eliminando archivo de ImageKit', ['exception' => $e]);
            return false;
        }
    }
}
