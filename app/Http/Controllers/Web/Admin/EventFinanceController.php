<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\EventFinanceAggregator;

class EventFinanceController extends Controller
{
    public function show(Event $event, EventFinanceAggregator $financeAggregator)
    {
        $this->authorize('viewFinancial', $event);

        $payload = $financeAggregator->adminFinance($event);

        return Inertia::render('Admin/Events/Finance', [
            'event' => $payload['event'],
            'finance' => $payload['finance'],
        ]);
    }

    public function updatePaymentStatus(Request $request, Event $event)
    {
        $this->authorize('viewFinancial', $event);

        $data = $request->validate([
            'is_paid' => ['required', 'boolean'],
        ]);

        $event->update(['is_paid' => (bool) $data['is_paid']]);

        return back()->with('success', 'Estado de pago actualizado');
    }
}
