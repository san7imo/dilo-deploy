<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventPaymentRequest;
use App\Http\Requests\UpdateEventPaymentRequest;
use App\Models\Event;
use App\Models\EventPayment;
use App\Services\EventPaymentService;

class EventPaymentController extends Controller
{
    /**
     * Registrar un pago del evento
     */
    public function store(
        StoreEventPaymentRequest $request,
        Event $event,
        EventPaymentService $service
    ) {
        $data = $request->validated();

        // Si es EUR, la tasa siempre es 1 
        if ($data['currency'] === 'EUR') {
            $data['exchange_rate_to_base'] = 1;
        }

        $service->create($event, $data);

        return back()->with('success', 'Pago registrado correctamente');
    }

    /**
     * Actualizar un pago existente
     */
    public function update(
        UpdateEventPaymentRequest $request,
        EventPayment $payment,
        EventPaymentService $service
    ) {
        $data = $request->validated();

        // Si es EUR, la tasa siempre es 1 
        if ($data['currency'] === 'EUR') {
            $data['exchange_rate_to_base'] = 1;
        }

        $service->update($payment, $data);

        return back()->with('success', 'Pago actualizado correctamente');
    }

    /**
     * Eliminar un pago
     */
    public function destroy(EventPayment $payment)
    {
        // Se valida contra el evento del pago
        $this->authorize('viewFinancial', $payment->event);

        $payment->delete();

        return back()->with('success', 'Pago eliminado correctamente');
    }
}
