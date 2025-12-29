<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventExpenseRequest;
use App\Http\Requests\UpdateEventExpenseRequest;
use App\Models\Event;
use App\Models\EventExpense;
use App\Services\EventExpenseService;

class EventExpenseController extends Controller
{
    /**
     * Registrar un gasto del evento
     */
    public function store(
        StoreEventExpenseRequest $request,
        Event $event,
        EventExpenseService $service
    ) {
        $data = $request->validated();

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file');
        }

        if ($data['currency'] === 'USD') {
            $data['exchange_rate_to_base'] = 1;
        }

        $service->create($event, $data);

        return back()->with('success', 'Gasto registrado correctamente');
    }

    /**
     * Actualizar un gasto existente
     */
    public function update(
        UpdateEventExpenseRequest $request,
        EventExpense $expense,
        EventExpenseService $service
    ) {
        $data = $request->validated();

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file');
        }

        if ($data['currency'] === 'USD') {
            $data['exchange_rate_to_base'] = 1;
        }

        $service->update($expense, $data);

        return back()->with('success', 'Gasto actualizado correctamente');
    }

    /**
     * Eliminar un gasto
     */
    public function destroy(EventExpense $expense, EventExpenseService $service)
    {
        $this->authorize('viewFinancial', $expense->event);

        $service->delete($expense);

        return back()->with('success', 'Gasto eliminado correctamente');
    }
}
