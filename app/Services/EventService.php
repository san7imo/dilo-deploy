<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class EventService
{
    /**
     * Obtener todos los eventos paginados.
     */
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return Event::with(['artists', 'mainArtist'])
            ->withSum('payments as total_paid_base', 'amount_base')
            ->withSum(
                ['payments as advance_paid_base' => function ($query) {
                    $query->where('is_advance', true);
                }],
                'amount_base'
            )
            ->withSum('expenses as total_expenses_base', 'amount_base')
            ->orderBy('event_date', 'desc')
            ->paginate($perPage);
    }

    public function getVisibleForUser(User $user, int $perPage = 10)
    {
        $query = Event::with(['artists', 'mainArtist'])
            ->withSum('payments as total_paid_base', 'amount_base')
            ->withSum(
                ['payments as advance_paid_base' => function ($query) {
                    $query->where('is_advance', true);
                }],
                'amount_base'
            )
            ->withSum('expenses as total_expenses_base', 'amount_base')
            ->orderBy('event_date', 'desc');

        if ($user->hasRole('contentmanager')) {
            return $query->paginate($perPage);
        }

        if ($user->hasRole('roadmanager')) {
            $query->whereHas('roadManagers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user->hasRole('artist') && $user->artist) {
            $query->where('main_artist_id', $user->artist->id);
        }

        // admin ve todo, no se filtra

        return $query->paginate($perPage);
    }

    /**
     * Obtener próximos eventos (fecha >= hoy)
     */
    public function getUpcoming(int $perPage = 12): LengthAwarePaginator
    {
        return Event::with(['artists', 'mainArtist'])
            ->whereDate('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->paginate($perPage);
    }

    /**
     * Obtener eventos pasados (fecha < hoy)
     */
    public function getPast(int $perPage = 12): LengthAwarePaginator
    {
        return Event::with(['artists', 'mainArtist'])
            ->whereDate('event_date', '<', now())
            ->orderBy('event_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Crear un evento y asociar artistas si vienen.
     */
    public function create(array $data): Event
    {
        // Manejar archivo de afiche si viene como UploadedFile 'poster_file'
        if (!empty($data['poster_file'])) {
            $imageKit = app(\App\Services\ImageKitService::class);
            $result = $imageKit->upload($data['poster_file'], '/events');
            if ($result) {
                $data['poster_url'] = $result['url'];
                $data['poster_id'] = $result['file_id'];
            }
            unset($data['poster_file']);
        }

        // Se separan artist_ids si vienen
        $artistIds = Arr::get($data, 'artist_ids', []);
        unset($data['artist_ids']);
        $roadManagerIds = Arr::get($data, 'road_manager_ids', []);
        unset($data['road_manager_ids']);

        if (!array_key_exists('is_paid', $data)) {
            $data['is_paid'] = false;
        }

        $event = Event::create($data);

        if (!empty($artistIds)) {
            $event->artists()->sync($artistIds);
        }

        if (is_array($roadManagerIds)) {
            $event->roadManagers()->sync($roadManagerIds);
        }

        return $event->load('artists');
    }

    /**
     * Obtener por id (o lanzar 404)
     */
    public function getById(int $id): Event
    {
        return Event::with(['artists', 'mainArtist', 'payments', 'expenses'])->findOrFail($id);
    }

    /**
     * Obtener por slug (o retornar null)
     */
    public function getBySlug(string $slug): ?Event
    {
        return Event::with(['artists', 'mainArtist', 'payments', 'expenses'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Actualizar evento (usa mismo request)
     */
    public function update(Event $event, array $data): Event
    {
        // Si viene un nuevo poster_file, eliminar antiguo y subir el nuevo
        if (!empty($data['poster_file'])) {
            $imageKit = app(\App\Services\ImageKitService::class);
            if ($event->poster_id) {
                $imageKit->delete($event->poster_id);
            }
            $result = $imageKit->upload($data['poster_file'], '/events');
            if ($result) {
                $data['poster_url'] = $result['url'];
                $data['poster_id'] = $result['file_id'];
            }
            unset($data['poster_file']);
        }

        $artistIds = Arr::get($data, 'artist_ids', null);
        unset($data['artist_ids']);
        $roadManagerIds = Arr::get($data, 'road_manager_ids', null);
        unset($data['road_manager_ids']);

        $event->update($data);

        if (is_array($artistIds)) {
            $event->artists()->sync($artistIds);
        }

        if (is_array($roadManagerIds)) {
            $event->roadManagers()->sync($roadManagerIds);
        }

        return $event->fresh('artists');
    }

    /**
     * Eliminar evento (limpia pivot)
     */
    public function delete(Event $event): void
    {
        $event->artists()->detach();
        $event->roadManagers()->detach();
        $event->delete();
    }
}
