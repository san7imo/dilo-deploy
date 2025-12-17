<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventExpenseRequest;
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

        if ($data['currency'] === 'EUR') {
            $data['exchange_rate_to_base'] = 1;
        }

        $service->create($event, $data);

        return back()->with('success', 'Gasto registrado correctamente');
    }

    /**
     * Eliminar un gasto
     */
    public function destroy(EventExpense $expense)
    {
        $this->authorize('viewFinancial', $expense->event);

        $expense->delete();

        return back()->with('success', 'Gasto eliminado correctamente');
    }
}
