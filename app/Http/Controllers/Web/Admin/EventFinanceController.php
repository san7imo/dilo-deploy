<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncEventExpensesRequest;
use App\Models\Collaborator;
use App\Models\Event;
use App\Models\EventExpense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\EventFinanceAggregator;
use Illuminate\Validation\ValidationException;

class EventFinanceController extends Controller
{
    public function show(Event $event, EventFinanceAggregator $financeAggregator)
    {
        $this->authorize('viewFinancial', $event);

        $user = request()->user();
        if ($user && $user->hasRole('roadmanager')) {
            $payload = $financeAggregator->roadManagerFinance($event, $user);
        } else {
            $payload = $financeAggregator->adminFinance($event);
        }

        return Inertia::render('Admin/Events/Finance', [
            'event' => $payload['event'],
            'finance' => $payload['finance'],
            'collaborators' => ($user && $user->hasRole('admin'))
                ? Collaborator::query()
                    ->orderBy('account_holder')
                    ->get(['id', 'account_holder', 'bank', 'account_number'])
                : [],
        ]);
    }

    public function updatePaymentStatus(Request $request, Event $event)
    {
        $this->authorize('viewFinancial', $event);

        $data = $request->validate([
            'is_paid' => ['required', 'boolean'],
        ]);

        if ($data['is_paid']) {
            $totalPaid = (float) $event->payments()->sum('amount_base');
            $feeTotal = (float) ($event->show_fee_total ?? 0);

            if ($feeTotal <= 0 || $totalPaid < $feeTotal) {
                throw ValidationException::withMessages([
                    'is_paid' => 'El total pagado debe ser igual o mayor al fee del show para marcarlo como pagado.',
                ]);
            }
        }

        $event->update(['is_paid' => (bool) $data['is_paid']]);

        return back()->with('success', 'Estado de pago actualizado');
    }

    public function updateEventDetails(Request $request, Event $event)
    {
        $this->authorize('viewFinancial', $event);

        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403);
        }

        $data = $request->validate([
            'event_date' => ['required', 'date'],
            'full_payment_due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:100'],
        ]);

        if (($data['status'] ?? null) === 'pagado' && $event->status !== 'pagado') {
            $totalPaid = (float) $event->payments()->sum('amount_base');
            $feeTotal = (float) ($event->show_fee_total ?? 0);

            if ($feeTotal <= 0 || $totalPaid < $feeTotal) {
                throw ValidationException::withMessages([
                    'status' => 'El total pagado debe ser igual o mayor al fee del show para marcarlo como pagado.',
                ]);
            }
        }

        $event->update($data);

        return back()->with('success', 'Datos del evento actualizados');
    }

    public function confirmRoadManagerPayment(Request $request, Event $event)
    {
        $this->authorize('viewFinancial', $event);

        $user = $request->user();
        if (!$user || !$user->hasRole('roadmanager')) {
            abort(403);
        }

        $confirmed = (bool) $request->boolean('confirmed', true);

        $event->roadManagers()->updateExistingPivot($user->id, [
            'payment_confirmed_at' => $confirmed ? now() : null,
        ]);

        return back()->with('success', 'Confirmacion de pago registrada');
    }

    /**
     * Sincroniza gastos dinámicos: crea, actualiza y elimina según el array enviado.
     */
    public function syncExpenses(SyncEventExpensesRequest $request, Event $event)
    {
        $this->authorize('viewFinancial', $event);

        $validated = $request->validated();
        $expensesData = $validated['expenses'];

        // Obtener IDs de gastos que vienen en el request
        $incomingIds = collect($expensesData)
            ->filter(fn($expense) => !is_null($expense['id']))
            ->pluck('id')
            ->toArray();

        // Eliminar gastos que no están en el array enviado
        EventExpense::where('event_id', $event->id)
            ->whereNotIn('id', $incomingIds)
            ->delete();

        // Crear o actualizar gastos
        foreach ($expensesData as $expenseData) {
            $expenseData['event_id'] = $event->id;

            // Calcular monto base si no se proporciona
            if (!isset($expenseData['exchange_rate_to_base']) || is_null($expenseData['exchange_rate_to_base'])) {
                $expenseData['exchange_rate_to_base'] = 1.0;
            }
            $expenseData['amount_base'] = $expenseData['amount_original'] * $expenseData['exchange_rate_to_base'];

            if (isset($expenseData['id']) && !is_null($expenseData['id'])) {
                // Actualizar gasto existente
                $expense = EventExpense::findOrFail($expenseData['id']);
                $expense->update($expenseData);
            } else {
                // Crear nuevo gasto
                EventExpense::create($expenseData);
            }
        }

        return back()->with('success', 'Gastos sincronizados correctamente');
    }
}
