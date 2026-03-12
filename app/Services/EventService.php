<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class EventService
{
    protected function eventRelations(): array
    {
        return [
            'artists',
            'mainArtist',
            'organizer',
        ];
    }

    /**
     * Obtener todos los eventos paginados.
     */
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return Event::with($this->eventRelations())
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

    public function getVisibleForUser(User $user, int $perPage = 10, array $filters = [])
    {
        $query = Event::with($this->eventRelations())
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
            $this->applySharedFilters($query, $filters);
            return $query->paginate($perPage)->withQueryString();
        }

        if ($user->hasRole('roadmanager')) {
            $query->whereHas('roadManagers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user->hasRole('artist') && $user->artist) {
            $query->where('main_artist_id', $user->artist->id);
        }

        // admin ve todo, no se filtra

        $this->applySharedFilters($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    protected function applySharedFilters($query, array $filters): void
    {
        $artistId = Arr::get($filters, 'artist_id');
        if (!empty($artistId)) {
            $query->where('main_artist_id', (int) $artistId);
        }

        $dateFrom = Arr::get($filters, 'date_from');
        if (!empty($dateFrom)) {
            $query->whereDate('event_date', '>=', $dateFrom);
        }

        $dateTo = Arr::get($filters, 'date_to');
        if (!empty($dateTo)) {
            $query->whereDate('event_date', '<=', $dateTo);
        }
    }

    /**
     * Obtener próximos eventos (fecha >= hoy)
     */
    public function getUpcoming(int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        return Event::with($this->eventRelations())
            ->whereDate('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    /**
     * Obtener eventos pasados (fecha < hoy)
     */
    public function getPast(int $perPage = 10, string $pageName = 'page'): LengthAwarePaginator
    {
        return Event::with($this->eventRelations())
            ->whereDate('event_date', '<', now())
            ->orderBy('event_date', 'desc')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
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

        $data = $this->hydrateOrganizerSnapshot($data);

        $event = Event::create($data);

        if (!empty($artistIds)) {
            $event->artists()->sync($artistIds);
        }

        if (is_array($roadManagerIds)) {
            $event->roadManagers()->sync($roadManagerIds);
        }

        return $event->load(['artists', 'organizer']);
    }

    /**
     * Obtener por id (o lanzar 404)
     */
    public function getById(int $id): Event
    {
        return Event::with([
            ...$this->eventRelations(),
            'payments',
            'expenses',
        ])->findOrFail($id);
    }

    /**
     * Obtener por slug (o retornar null)
     */
    public function getBySlug(string $slug): ?Event
    {
        return Event::with([
            ...$this->eventRelations(),
            'payments',
            'expenses',
        ])
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

        $data = $this->hydrateOrganizerSnapshot($data);

        $event->update($data);

        if (is_array($artistIds)) {
            $event->artists()->sync($artistIds);
        }

        if (is_array($roadManagerIds)) {
            $event->roadManagers()->sync($roadManagerIds);
        }

        return $event->fresh(['artists', 'organizer']);
    }

    protected function hydrateOrganizerSnapshot(array $data): array
    {
        $snapshotFields = [
            'organizer_company_name',
            'organizer_contact_name',
            'organizer_logo_url',
            'organizer_website',
            'organizer_instagram_url',
            'organizer_facebook_url',
            'organizer_tiktok_url',
            'organizer_x_url',
            'organizer_whatsapp',
            'organizer_email',
        ];

        $organizerId = Arr::get($data, 'organizer_id');
        if (!$organizerId) {
            foreach ($snapshotFields as $field) {
                $data[$field] = null;
            }
            return $data;
        }

        $organizer = Organizer::query()->find($organizerId);
        if (!$organizer) {
            return $data;
        }

        $snapshotMap = [
            'organizer_company_name' => $organizer->company_name,
            'organizer_contact_name' => $organizer->contact_name,
            'organizer_logo_url' => $organizer->logo_url,
            'organizer_website' => $organizer->website,
            'organizer_instagram_url' => $organizer->instagram_url,
            'organizer_facebook_url' => $organizer->facebook_url,
            'organizer_tiktok_url' => $organizer->tiktok_url,
            'organizer_x_url' => $organizer->x_url,
            'organizer_whatsapp' => $organizer->whatsapp,
            'organizer_email' => $organizer->email,
        ];

        foreach ($snapshotMap as $field => $value) {
            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * Eliminar evento (limpia pivot)
     */
    public function delete(Event $event): void
    {
        $event->delete();
    }
}
