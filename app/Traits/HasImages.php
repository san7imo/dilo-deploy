<?php

namespace App\Traits;

use App\Services\ImageKitService;
use Illuminate\Support\Facades\Log;

/**
 * Trait que gestiona automáticamente las imágenes asociadas a un modelo.
 * Permite eliminar los archivos de ImageKit cuando el modelo se borra o actualiza.
 */
trait HasImages
{
    /**
     * Define los campos de imagen que deben manejarse (urls e ids)
     * Debe sobrescribirse en cada modelo que use esta trait.
     */

    protected static function bootHasImages()
    {
        static::saving(function ($model) {
            // Antes de guardar, revisar si hay campos *_file asignados y subirlos
            $imageKit = app(ImageKitService::class);
            foreach ($model->getImageFieldPairs() as $urlField => $idField) {
                // nombre base del campo (sin _url/_id)
                $base = preg_replace('/(_url|_id)$/', '', $urlField);
                $tmpFileField = $base . '_file';

                if (isset($model->$tmpFileField) && $model->$tmpFileField) {
                    // Si hay un id previo y existe, eliminarlo
                    if (!empty($model->$idField)) {
                        try {
                            $imageKit->delete($model->$idField);
                        } catch (\Throwable $e) {
                            Log::warning("No se pudo eliminar imagen previa de ImageKit: {$model->$idField}");
                        }
                    }

                    // Subir el nuevo archivo
                    try {
                        $result = $imageKit->upload($model->$tmpFileField, '/' . strtolower(class_basename($model)));
                        if ($result) {
                            $model->$urlField = $result['url'];
                            $model->$idField = $result['file_id'];
                        }
                    } catch (\Throwable $e) {
                        Log::error('Error subiendo imagen desde HasImages trait', ['exception' => $e]);
                    }

                    // Remover la propiedad temporal para que no cause problemas al serializar
                    unset($model->$tmpFileField);
                }
            }
        });

        static::deleting(function ($model) {
            $imageKit = app(ImageKitService::class);
            foreach ($model->getImageFieldPairs() as $urlField => $idField) {
                if ($model->$idField) {
                    try {
                        $imageKit->delete($model->$idField);
                    } catch (\Throwable $e) {
                        Log::warning("No se pudo eliminar imagen de ImageKit: {$model->$idField}");
                    }
                }
            }
        });
    }

    /**
     * Retorna un array de pares [url_field => id_field]
     */
    public function getImageFieldPairs(): array
    {
        $pairs = [];
        foreach ($this->imageFields as $field) {
            $pairs[$field . '_url'] = $field . '_id';
        }
        return $pairs;
    }
}
