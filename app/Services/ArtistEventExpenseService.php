<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ArtistEventExpense;
use App\Models\User;
use App\Services\ImageKitService;

class ArtistEventExpenseService
{
    /**
     * Crear un gasto personal del artista normalizado a USD.
     */
    public function create(Event $event, int $artistId, User $createdBy, array $data): ArtistEventExpense
    {
        // Subir comprobante si existe
        if (!empty($data['receipt_file'])) {
            $imageKit = app(ImageKitService::class);
            $result = $imageKit->upload($data['receipt_file'], '/artist-event-expenses');
            if ($result) {
                $data['receipt_url'] = $result['url'];
                $data['receipt_id'] = $result['file_id'];
            }
            unset($data['receipt_file']);
        }

        // Normalizar a USD
        if ($data['currency'] === 'USD') {
            $data['exchange_rate_to_base'] = 1;
            $data['amount_base'] = $data['amount_original'];
        } else {
            $data['amount_base'] = round(
                $data['amount_original'] / $data['exchange_rate_to_base'],
                2
            );
        }

        // Establecer relaciones
        $data['event_id'] = $event->id;
        $data['artist_id'] = $artistId;
        $data['created_by_user_id'] = $createdBy->id;

        $expense = ArtistEventExpense::create($data);

        return $expense;
    }

    /**
     * Actualizar un gasto personal del artista
     */
    public function update(ArtistEventExpense $expense, array $data): ArtistEventExpense
    {
        // Actualizar comprobante si viene uno nuevo
        if (!empty($data['receipt_file'])) {
            $imageKit = app(ImageKitService::class);
            if (!empty($expense->receipt_id)) {
                $imageKit->delete($expense->receipt_id);
            }
            $result = $imageKit->upload($data['receipt_file'], '/artist-event-expenses');
            if ($result) {
                $data['receipt_url'] = $result['url'];
                $data['receipt_id'] = $result['file_id'];
            }
            unset($data['receipt_file']);
        }

        // Normalizar a USD
        if ($data['currency'] === 'USD') {
            $data['exchange_rate_to_base'] = 1;
            $data['amount_base'] = $data['amount_original'];
        } else {
            $data['amount_base'] = round(
                $data['amount_original'] / $data['exchange_rate_to_base'],
                2
            );
        }

        $expense->update($data);

        return $expense;
    }

    /**
     * Aprobar un gasto personal del artista
     */
    public function approve(ArtistEventExpense $expense): ArtistEventExpense
    {
        $expense->approve();
        return $expense;
    }

    /**
     * Eliminar un gasto personal del artista
     */
    public function delete(ArtistEventExpense $expense): void
    {
        if (!empty($expense->receipt_id)) {
            $imageKit = app(ImageKitService::class);
            $imageKit->delete($expense->receipt_id);
        }
        $expense->delete();
    }

    /**
     * Obtener gastos personales del artista para un evento
     */
    public function getByEventAndArtist(Event $event, int $artistId)
    {
        return ArtistEventExpense::byEvent($event->id)
            ->byArtist($artistId)
            ->with('createdByUser:id,name,email')
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calcular el total de gastos personales aprobados del artista en un evento
     */
    public function getTotalApprovedByEventAndArtist(Event $event, int $artistId): float
    {
        return ArtistEventExpense::byEvent($event->id)
            ->byArtist($artistId)
            ->approved()
            ->sum('amount_base');
    }

    /**
     * Obtener resumen de gastos por categoría
     */
    public function getSummaryByCategory(Event $event, int $artistId): array
    {
        return ArtistEventExpense::byEvent($event->id)
            ->byArtist($artistId)
            ->approved()
            ->selectRaw('category, SUM(amount_base) as total')
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category')
            ->toArray();
    }
}
