<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventExpense;
use App\Services\ImageKitService;

class EventExpenseService
{
    /**
     * Crear un gasto normalizado a USD.
     */
    public function create(Event $event, array $data): EventExpense
    {
        if (!empty($data['receipt_file'])) {
            $imageKit = app(ImageKitService::class);
            $result = $imageKit->upload($data['receipt_file'], '/event-expenses');
            if ($result) {
                $data['receipt_url'] = $result['url'];
                $data['receipt_id'] = $result['file_id'];
            }
            unset($data['receipt_file']);
        }

        if ($data['currency'] === 'USD') {
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
        if (!empty($data['receipt_file'])) {
            $imageKit = app(ImageKitService::class);
            if (!empty($expense->receipt_id)) {
                $imageKit->delete($expense->receipt_id);
            }
            $result = $imageKit->upload($data['receipt_file'], '/event-expenses');
            if ($result) {
                $data['receipt_url'] = $result['url'];
                $data['receipt_id'] = $result['file_id'];
            }
            unset($data['receipt_file']);
        }

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

    public function delete(EventExpense $expense): void
    {
        if (!empty($expense->receipt_id)) {
            $imageKit = app(ImageKitService::class);
            $imageKit->delete($expense->receipt_id);
        }
        $expense->delete();
    }
}
