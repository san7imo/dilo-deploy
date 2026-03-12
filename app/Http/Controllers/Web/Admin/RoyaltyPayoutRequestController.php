<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoyaltyPayoutPaymentRequest;
use App\Models\RoyaltyPayoutRequest;
use App\Services\RoyaltyPayoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoyaltyPayoutRequestController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = strtolower(trim((string) $request->query('status', 'all')));
        if (!in_array($statusFilter, ['all', 'pending', 'approved', 'paid', 'rejected'], true)) {
            $statusFilter = 'all';
        }

        $requestsQuery = RoyaltyPayoutRequest::query()
            ->with([
                'requesterArtist:id,name',
                'requesterUser:id,name,email',
            ])
            ->withSum('payments as paid_total_usd', 'amount_usd')
            ->orderByDesc('requested_at')
            ->orderByDesc('id');

        if ($statusFilter !== 'all') {
            $requestsQuery->where('status', $statusFilter);
        }

        $requests = $requestsQuery
            ->paginate(20)
            ->withQueryString()
            ->through(function (RoyaltyPayoutRequest $item) {
                $requested = (float) $item->requested_amount_usd;
                $paid = (float) ($item->paid_total_usd ?? 0);
                $outstanding = max($requested - $paid, 0);

                return [
                    'id' => $item->id,
                    'requester_name' => $item->requester_name,
                    'requester_email' => $item->requester_email,
                    'requested_amount_usd' => $requested,
                    'paid_total_usd' => $paid,
                    'outstanding_usd' => $outstanding,
                    'status' => $item->status,
                    'requested_at' => $item->requested_at,
                ];
            });

        $kpis = [
            'pending_count' => RoyaltyPayoutRequest::query()
                ->whereIn('status', ['pending', 'approved'])
                ->count(),
            'pending_amount_usd' => (float) RoyaltyPayoutRequest::query()
                ->whereIn('status', ['pending', 'approved'])
                ->sum('requested_amount_usd'),
            'paid_amount_usd' => (float) RoyaltyPayoutRequest::query()
                ->where('status', 'paid')
                ->sum('requested_amount_usd'),
        ];

        return Inertia::render('Admin/Royalties/PayoutRequests/Index', [
            'requests' => $requests,
            'kpis' => $kpis,
            'filters' => [
                'status' => $statusFilter,
            ],
        ]);
    }

    public function show(RoyaltyPayoutRequest $payoutRequest)
    {
        $payoutRequest->load([
            'requesterArtist:id,name',
            'requesterUser:id,name,email',
            'payments.creator:id,name',
        ]);

        $payments = $payoutRequest->payments
            ->sortByDesc('paid_at')
            ->values()
            ->map(fn($payment) => [
                'id' => $payment->id,
                'amount_usd' => (float) $payment->amount_usd,
                'payment_method' => $payment->payment_method,
                'payment_reference' => $payment->payment_reference,
                'paid_at' => optional($payment->paid_at)->toDateString(),
                'description' => $payment->description,
                'created_by' => $payment->creator?->name,
                'created_at' => $payment->created_at,
            ]);

        $paidTotal = (float) $payments->sum('amount_usd');
        $requestedAmount = (float) $payoutRequest->requested_amount_usd;

        return Inertia::render('Admin/Royalties/PayoutRequests/Show', [
            'payoutRequest' => [
                'id' => $payoutRequest->id,
                'requester_name' => $payoutRequest->requester_name,
                'requester_email' => $payoutRequest->requester_email,
                'requested_amount_usd' => $requestedAmount,
                'status' => $payoutRequest->status,
                'requested_at' => $payoutRequest->requested_at,
                'admin_notes' => $payoutRequest->admin_notes,
            ],
            'payments' => $payments,
            'summary' => [
                'paid_total_usd' => $paidTotal,
                'outstanding_usd' => max($requestedAmount - $paidTotal, 0),
            ],
        ]);
    }

    public function storePayment(
        StoreRoyaltyPayoutPaymentRequest $request,
        RoyaltyPayoutRequest $payoutRequest,
        RoyaltyPayoutService $royaltyPayoutService
    ) {
        if ($payoutRequest->status === 'rejected') {
            return back()->withErrors([
                'payout_request' => 'No puedes registrar pagos en una solicitud rechazada.',
            ]);
        }

        $royaltyPayoutService->registerPayment(
            $payoutRequest,
            $request->validated(),
            $request->user()->id
        );

        return back()->with('success', 'Pago de regalías registrado correctamente.');
    }
}
