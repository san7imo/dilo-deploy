<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Inertia\Inertia;

class EventFinanceController extends Controller
{
    public function show(Event $event)
    {
        
        $this->authorize('viewFinancial', $event);

        $event->load([
            'mainArtist:id,name',
            'payments' => fn($q) => $q
                ->orderBy('payment_date', 'desc')
                ->orderBy('created_at', 'desc'),
        ]);

        $totalPaid = $event->payments->sum('amount_base');
        $advancePaid = $event->payments->where('is_advance', true)->sum('amount_base');

        return Inertia::render('Admin/Events/Finance', [
            'event' => $event,
            'finance' => [
                'total_paid_base' => round($totalPaid, 2),
                'advance_paid_base' => round($advancePaid, 2),
            ],
        ]);
    }
}
