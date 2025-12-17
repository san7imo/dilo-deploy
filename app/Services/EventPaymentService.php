<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPayment;

class EventPaymentService
{
    /**
     * Crear un pago normalizado a EUR (moneda base)
     */
    public function create(Event $event, array $data): EventPayment
    {
        // Si la moneda ya es EUR, el monto base es el mismo
        if ($data['currency'] === 'EUR') {
            $data['exchange_rate_to_base'] = 1;
            $data['amount_base'] = $data['amount_original'];
        } else {
            // amount_original / rate = EUR
            $data['amount_base'] = round(
                $data['amount_original'] / $data['exchange_rate_to_base'],
                2
            );
        }

        $payment = $event->payments()->create($data);

        $this->refreshPaidStatus($event);

        return $payment;
    }

    public function delete(EventPayment $payment): void
    {
        $event = $payment->event;

        $payment->delete();

        if ($event) {
            $this->refreshPaidStatus($event);
        }
    }

    /**
     * Sincroniza el estado de pago a partir del total ingresado.
     */
    protected function refreshPaidStatus(Event $event): void
    {
        $total = $event->payments()->sum('amount_base');
        $event->is_paid = $total > 0;
        $event->save();
    }
}
