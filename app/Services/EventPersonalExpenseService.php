<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPersonalExpense;
use Illuminate\Validation\ValidationException;

class EventPersonalExpenseService
{
    /**
     * Crear un gasto personal normalizado a USD.
     */
    public function create(Event $event, array $data): EventPersonalExpense
    {
        if (empty($event->main_artist_id)) {
            throw ValidationException::withMessages([
                'base' => 'El evento no tiene artista principal asignado.',
            ]);
        }

        $data = $this->normalizeAmounts($data);
        $this->ensureWithinArtistShare($event, $data['amount_base']);

        $data['artist_id'] = $event->main_artist_id;

        return $event->personalExpenses()->create($data);
    }

    /**
     * Actualizar un gasto personal existente.
     */
    public function update(EventPersonalExpense $expense, array $data): EventPersonalExpense
    {
        $data = $this->normalizeAmounts($data);

        $this->ensureWithinArtistShare($expense->event, $data['amount_base'], $expense);

        $expense->update($data);

        return $expense;
    }

    public function delete(EventPersonalExpense $expense): void
    {
        $expense->delete();
    }

    protected function normalizeAmounts(array $data): array
    {
        if (($data['currency'] ?? null) === 'USD') {
            $data['exchange_rate_to_base'] = 1;
            $data['amount_base'] = $data['amount_original'];
            return $data;
        }

        $data['amount_base'] = round(
            $data['amount_original'] / $data['exchange_rate_to_base'],
            2
        );

        return $data;
    }

    protected function ensureWithinArtistShare(
        Event $event,
        float $amountBase,
        ?EventPersonalExpense $ignoreExpense = null
    ): void {
        $totalPaid = (float) $event->payments()->sum('amount_base');
        $totalExpenses = (float) $event->expenses()->sum('amount_base');
        $net = $totalPaid - $totalExpenses;

        $artistShare = round(max($net * 0.70, 0), 2);

        $personalTotal = (float) $event->personalExpenses()->sum('amount_base');
        if ($ignoreExpense) {
            $personalTotal -= (float) ($ignoreExpense->amount_base ?? 0);
        }

        $remaining = round($artistShare - $personalTotal, 2);

        if ($remaining <= 0 && $amountBase > 0) {
            throw ValidationException::withMessages([
                'amount_original' => 'No puedes agregar más gastos personales porque el 70% del artista ya está en 0.',
            ]);
        }

        if ($remaining > 0 && $amountBase > $remaining) {
            $formatted = number_format($remaining, 2);
            throw ValidationException::withMessages([
                'amount_original' => "El gasto personal supera el 70% disponible del artista. Disponible: USD {$formatted}.",
            ]);
        }
    }
}
