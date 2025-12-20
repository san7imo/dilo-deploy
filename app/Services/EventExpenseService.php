<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventExpense;

class EventExpenseService
{
    /**
     * Crear un gasto normalizado a EUR.
     */
    public function create(Event $event, array $data): EventExpense
    {
        if ($data['currency'] === 'EUR') {
            $data['exchange_rate_to_base'] = 1;
            $data['amount_base'] = $data['amount_original'];
        } else {
            $data['amount_base'] = round(
                $data['amount_original'] / $data['exchange_rate_to_base'],
                2
            );
        }

        $expense = $event->expenses()->create($data);

        return $expense;
    }

    /**
     * Actualizar un gasto existente
     */
    public function update(EventExpense $expense, array $data): EventExpense
    {
        if ($data['currency'] === 'EUR') {
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

    public function delete(EventExpense $expense): void
    {
        $expense->delete();
    }
}
