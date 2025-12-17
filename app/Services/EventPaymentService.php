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

return $event->payments()->create($data);
}

public function delete(EventPayment $payment): void
{
$payment->delete();
}
}