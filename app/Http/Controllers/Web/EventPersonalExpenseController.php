<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventPersonalExpenseRequest;
use App\Http\Requests\UpdateEventPersonalExpenseRequest;
use App\Models\Event;
use App\Models\EventPersonalExpense;
use App\Services\EventPersonalExpenseService;

class EventPersonalExpenseController extends Controller
{
    /**
     * Registrar un gasto personal del artista.
     */
    public function store(
        StoreEventPersonalExpenseRequest $request,
        Event $event,
        EventPersonalExpenseService $service
    ) {
        $data = $request->validated();

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file');
        }

        $service->create($event, $data);

        return back()->with('success', 'Gasto personal registrado correctamente');
    }

    /**
     * Actualizar un gasto personal existente.
     */
    public function update(
        UpdateEventPersonalExpenseRequest $request,
        EventPersonalExpense $personalExpense,
        EventPersonalExpenseService $service
    ) {
        $data = $request->validated();

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file');
        }

        $service->update($personalExpense, $data);

        return back()->with('success', 'Gasto personal actualizado correctamente');
    }

    /**
     * Eliminar un gasto personal.
     */
    public function destroy(EventPersonalExpense $personalExpense, EventPersonalExpenseService $service)
    {
        $this->authorize('viewFinancial', $personalExpense->event);

        $service->delete($personalExpense);

        return back()->with('success', 'Gasto personal eliminado correctamente');
    }
}
