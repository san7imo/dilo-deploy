<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPayment;
use App\Services\ImageKitService;

class EventPaymentService
{
    /**
     * Crear un pago normalizado a USD (moneda base)
     */
    public function create(Event $event, array $data): EventPayment
    {
        if (!empty($data['receipt_file'])) {
            $imageKit = app(ImageKitService::class);
            $result = $imageKit->upload($data['receipt_file'], '/event-payments');
            if ($result) {
                $data['receipt_url'] = $result['url'];
                $data['receipt_id'] = $result['file_id'];
            }
            unset($data['receipt_file']);
        }

        // Si la moneda ya es USD, el monto base es el mismo
        if ($data['currency'] === 'USD') {
            $data['exchange_rate_to_base'] = 1;
            $data['amount_base'] = $data['amount_original'];
        } else {
            // amount_original / rate = USD
            $data['amount_base'] = round(
                $data['amount_original'] / $data['exchange_rate_to_base'],
                2
            );
        }

        $payment = $event->payments()->create($data);

        $this->refreshPaidStatus($event);

        return $payment;
    }

    /**
     * Actualizar un pago existente
     */
    public function update(EventPayment $payment, array $data): EventPayment
    {
        $event = $payment->event;

        if (!empty($data['receipt_file'])) {
            $imageKit = app(ImageKitService::class);
            if (!empty($payment->receipt_id)) {
                $imageKit->delete($payment->receipt_id);
            }
            $result = $imageKit->upload($data['receipt_file'], '/event-payments');
            if ($result) {
                $data['receipt_url'] = $result['url'];
                $data['receipt_id'] = $result['file_id'];
            }
            unset($data['receipt_file']);
        }

        // Si la moneda es USD, el monto base es el mismo
        if ($data['currency'] === 'USD') {
            $data['exchange_rate_to_base'] = 1;
            $data['amount_base'] = $data['amount_original'];
        } else {
            // amount_original / rate = USD
            $data['amount_base'] = round(
                $data['amount_original'] / $data['exchange_rate_to_base'],
                2
            );
        }

        $payment->update($data);

        if ($event) {
            $this->refreshPaidStatus($event);
        }

        return $payment;
    }

    public function delete(EventPayment $payment): void
    {
        $event = $payment->event;

        if (!empty($payment->receipt_id)) {
            $imageKit = app(ImageKitService::class);
            $imageKit->delete($payment->receipt_id);
        }

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
        $total = (float) $event->payments()->sum('amount_base');
        $feeTotal = (float) ($event->show_fee_total ?? 0);

        $event->is_paid = $feeTotal > 0 && $total >= $feeTotal;
        $event->save();
    }
}
