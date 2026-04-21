<?php

namespace App\Services;

use App\Mail\RoyaltyPayoutRequestedMail;
use App\Models\RoyaltyAllocation;
use App\Models\RoyaltyPayoutItem;
use App\Models\RoyaltyPayoutPayment;
use App\Models\RoyaltyPayoutRequest;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoyaltyPayoutService
{
    public function resolveAccessContext(User $user): array
    {
        $artist = $user->artist()->first();

        if ($artist && $artist->artist_origin === 'internal') {
            return [
                'type' => 'internal',
                'artist_id' => $artist->id,
                'user_id' => $user->id,
                'email' => strtolower((string) $user->email),
                'name' => $artist->name ?: $user->name,
            ];
        }

        if ($user->hasRole('external_artist')) {
            return [
                'type' => 'external',
                'artist_id' => null,
                'user_id' => $user->id,
                'email' => strtolower((string) $user->email),
                'name' => $user->stage_name ?: $user->name,
            ];
        }

        abort(403);
    }

    public function buildArtistRoyaltyOverview(array $access): array
    {
        if (!Schema::hasTable('royalty_allocations') || !Schema::hasTable('royalty_statements')) {
            return $this->emptyOverview();
        }

        $allocationBase = DB::table('royalty_allocations as ra')
            ->join('royalty_statements as rs', 'rs.id', '=', 'ra.royalty_statement_id')
            ->where('rs.status', 'processed')
            ->where('rs.is_current', true);

        if (Schema::hasColumn('royalty_statements', 'is_reference_only')) {
            $allocationBase->where('rs.is_reference_only', false);
        }

        $this->applyAllocationOwnershipFilter($allocationBase, $access);

        $totalAccruedUsd = (float) ((clone $allocationBase)->sum('ra.allocated_amount_usd') ?? 0);

        $periodRows = (clone $allocationBase)
            ->groupBy('rs.reporting_month_date', 'rs.reporting_period')
            ->orderByDesc('rs.reporting_month_date')
            ->selectRaw('
                rs.reporting_month_date,
                rs.reporting_period,
                COALESCE(SUM(ra.allocated_amount_usd),0) as total_usd
            ')
            ->get()
            ->map(fn($row) => [
                'reporting_month_date' => $row->reporting_month_date,
                'reporting_period' => $row->reporting_period,
                'total_usd' => (float) ($row->total_usd ?? 0),
            ])
            ->values();

        $openStatuses = ['pending', 'approved'];
        $openRequestedUsd = 0.0;
        $openRequestsCount = 0;
        $totalPaidUsd = 0.0;
        $lastRequest = null;

        if (Schema::hasTable('royalty_payout_requests')) {
            $requestsBase = DB::table('royalty_payout_requests as rpr');
            $this->applyPayoutOwnershipFilter($requestsBase, $access);

            $openRequestedUsd = (float) ((clone $requestsBase)
                ->whereIn('rpr.status', $openStatuses)
                ->sum('rpr.requested_amount_usd') ?? 0);

            $openRequestsCount = (int) ((clone $requestsBase)
                ->whereIn('rpr.status', $openStatuses)
                ->count());

            $lastRequest = (clone $requestsBase)
                ->orderByDesc('rpr.requested_at')
                ->select([
                    'rpr.id',
                    'rpr.status',
                    'rpr.requested_amount_usd',
                    'rpr.requested_at',
                ])
                ->first();
        }

        $totalPaidUsd = $this->calculateTotalPaidUsd($access);

        $availableToRequestUsd = round(max($totalAccruedUsd - $totalPaidUsd - $openRequestedUsd, 0), 6);
        $minimumThresholdUsd = 50.0;

        return [
            'total_accrued_usd' => round($totalAccruedUsd, 6),
            'total_paid_usd' => round($totalPaidUsd, 6),
            'pending_requested_usd' => round($openRequestedUsd, 6),
            'available_to_request_usd' => $availableToRequestUsd,
            'minimum_threshold_usd' => $minimumThresholdUsd,
            'can_request_payment' => $availableToRequestUsd >= $minimumThresholdUsd,
            'open_requests_count' => $openRequestsCount,
            'period_totals' => $periodRows,
            'last_request' => $lastRequest ? [
                'id' => (int) $lastRequest->id,
                'status' => $lastRequest->status,
                'requested_amount_usd' => (float) ($lastRequest->requested_amount_usd ?? 0),
                'requested_at' => $lastRequest->requested_at,
            ] : null,
        ];
    }

    public function createPayoutRequest(array $access, User $user, float $amountUsd): RoyaltyPayoutRequest
    {
        $request = RoyaltyPayoutRequest::create([
            'requester_user_id' => $access['user_id'],
            'requester_artist_id' => $access['artist_id'],
            'requester_name' => $access['name'] ?: $user->name,
            'requester_email' => $access['email'] ?: strtolower((string) $user->email),
            'requested_amount_usd' => number_format($amountUsd, 6, '.', ''),
            'minimum_threshold_usd' => number_format(50, 6, '.', ''),
            'currency' => 'USD',
            'status' => 'pending',
            'requested_at' => now(),
            'metadata' => [
                'access_type' => $access['type'],
            ],
        ]);

        $this->syncAllocationStatusesForAccess($access, 'pending');

        return $request;
    }

    public function registerPayment(
        RoyaltyPayoutRequest $payoutRequest,
        array $payload,
        ?int $adminUserId = null
    ): RoyaltyPayoutPayment {
        return DB::transaction(function () use ($payoutRequest, $payload, $adminUserId) {
            $paymentAmount = round((float) $payload['amount_usd'], 6);
            if ($paymentAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount_usd' => 'El monto del pago debe ser mayor a cero.',
                ]);
            }

            $alreadyPaid = (float) $payoutRequest->payments()->sum('amount_usd');
            $remainingRequestAmount = round(max((float) $payoutRequest->requested_amount_usd - $alreadyPaid, 0), 6);
            if ($paymentAmount - $remainingRequestAmount > 0.000001) {
                throw ValidationException::withMessages([
                    'amount_usd' => 'El monto excede el pendiente de la solicitud.',
                ]);
            }

            $payment = $payoutRequest->payments()->create([
                'amount_usd' => number_format($paymentAmount, 6, '.', ''),
                'currency' => 'USD',
                'payment_method' => $payload['payment_method'] ?? null,
                'payment_reference' => $payload['payment_reference'] ?? null,
                'paid_at' => $payload['paid_at'],
                'description' => $payload['description'] ?? null,
                'created_by' => $adminUserId,
            ]);

            if (Schema::hasTable('royalty_payout_items')) {
                $this->allocatePaymentToAllocations($payoutRequest, $payment, $paymentAmount);
            }

            $paidTotal = (float) $payoutRequest->payments()->sum('amount_usd');
            $requestedAmount = (float) $payoutRequest->requested_amount_usd;
            $isFullyPaid = ($paidTotal + 0.000001) >= $requestedAmount;

            $payoutRequest->update([
                'status' => $isFullyPaid ? 'paid' : 'approved',
                'processed_at' => $isFullyPaid ? now() : $payoutRequest->processed_at,
                'processed_by' => $adminUserId ?: $payoutRequest->processed_by,
            ]);

            $access = $this->buildAccessFromPayoutRequest($payoutRequest->fresh());
            $openRequestStatus = $this->resolveOpenRequestStatus($access);
            $this->syncAllocationStatusesForAccess($access, $openRequestStatus);

            return $payment;
        });
    }

    public function syncAllocationStatusesForAccess(array $access, ?string $openRequestStatus = null): void
    {
        if (!Schema::hasTable('royalty_allocations')) {
            return;
        }

        $baseStatus = 'accrued';
        if ($openRequestStatus === 'approved') {
            $baseStatus = 'payable';
        } elseif ($openRequestStatus === 'pending') {
            $baseStatus = 'approved';
        }

        if (!Schema::hasTable('royalty_payout_items')) {
            $query = DB::table('royalty_allocations as ra');
            $this->applyAllocationOwnershipFilter($query, $access, 'ra');
            $query->where('ra.status', '!=', 'paid')
                ->update([
                    'status' => $baseStatus,
                    'updated_at' => now(),
                ]);
            return;
        }

        $query = DB::table('royalty_allocations as ra')
            ->leftJoin('royalty_payout_items as rpi', 'rpi.royalty_allocation_id', '=', 'ra.id');

        $this->applyAllocationOwnershipFilter($query, $access, 'ra');

        $rows = $query
            ->groupBy('ra.id', 'ra.allocated_amount_usd')
            ->selectRaw('ra.id, ra.allocated_amount_usd, COALESCE(SUM(rpi.amount_usd),0) as paid_amount_usd')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $byStatus = [
            'accrued' => [],
            'approved' => [],
            'payable' => [],
            'paid' => [],
        ];

        foreach ($rows as $row) {
            $allocated = (float) ($row->allocated_amount_usd ?? 0);
            $paid = (float) ($row->paid_amount_usd ?? 0);
            $remaining = $allocated - $paid;

            if ($remaining <= 0.000001) {
                $byStatus['paid'][] = (int) $row->id;
                continue;
            }

            if ($baseStatus === 'payable') {
                $byStatus['payable'][] = (int) $row->id;
                continue;
            }

            if ($baseStatus === 'approved') {
                $byStatus['approved'][] = (int) $row->id;
                continue;
            }

            $byStatus['accrued'][] = (int) $row->id;
        }

        $now = now();
        foreach ($byStatus as $status => $ids) {
            if (empty($ids)) {
                continue;
            }

            RoyaltyAllocation::query()
                ->whereIn('id', $ids)
                ->update([
                    'status' => $status,
                    'updated_at' => $now,
                ]);
        }
    }

    public function notifyAdminsAboutPayoutRequest(RoyaltyPayoutRequest $payoutRequest): void
    {
        $emails = User::role('admin')
            ->whereNotNull('email')
            ->pluck('email')
            ->filter(fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn($email) => Str::lower((string) $email))
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return;
        }

        try {
            foreach ($emails as $email) {
                Mail::to($email)->send(new RoyaltyPayoutRequestedMail($payoutRequest));
            }
        } catch (\Throwable $exception) {
            Log::error('No se pudo enviar notificación de solicitud de pago', [
                'payout_request_id' => $payoutRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function applyAllocationOwnershipFilter(Builder $query, array $access, string $alias = 'ra'): void
    {
        $query->where(function ($ownershipQuery) use ($access, $alias) {
            $hasCondition = false;

            if ($access['type'] === 'internal') {
                if (!empty($access['artist_id'])) {
                    $ownershipQuery->where("{$alias}.party_artist_id", $access['artist_id']);
                    $hasCondition = true;
                }

                if (!empty($access['user_id'])) {
                    if ($hasCondition) {
                        $ownershipQuery->orWhere("{$alias}.party_user_id", $access['user_id']);
                    } else {
                        $ownershipQuery->where("{$alias}.party_user_id", $access['user_id']);
                        $hasCondition = true;
                    }
                }

                if (!empty($access['email'])) {
                    $ownershipQuery->orWhereRaw("LOWER({$alias}.party_email) = ?", [$access['email']]);
                    $hasCondition = true;
                }

                if (!$hasCondition) {
                    $ownershipQuery->whereRaw('1 = 0');
                }

                return;
            }

            if (!empty($access['user_id'])) {
                $ownershipQuery->where("{$alias}.party_user_id", $access['user_id']);
                $hasCondition = true;
            }

            if (!empty($access['email'])) {
                if ($hasCondition) {
                    $ownershipQuery->orWhereRaw("LOWER({$alias}.party_email) = ?", [$access['email']]);
                } else {
                    $ownershipQuery->whereRaw("LOWER({$alias}.party_email) = ?", [$access['email']]);
                    $hasCondition = true;
                }
            }

            if (!$hasCondition) {
                $ownershipQuery->whereRaw('1 = 0');
            }
        });
    }

    public function applyPayoutOwnershipFilter($query, array $access, string $alias = 'rpr'): void
    {
        $query->where(function ($ownershipQuery) use ($access, $alias) {
            $hasCondition = false;

            if ($access['type'] === 'internal') {
                if (!empty($access['artist_id'])) {
                    $ownershipQuery->where("{$alias}.requester_artist_id", $access['artist_id']);
                    $hasCondition = true;
                }

                if (!empty($access['user_id'])) {
                    if ($hasCondition) {
                        $ownershipQuery->orWhere("{$alias}.requester_user_id", $access['user_id']);
                    } else {
                        $ownershipQuery->where("{$alias}.requester_user_id", $access['user_id']);
                        $hasCondition = true;
                    }
                }

                if (!empty($access['email'])) {
                    $ownershipQuery->orWhereRaw("LOWER({$alias}.requester_email) = ?", [$access['email']]);
                    $hasCondition = true;
                }

                if (!$hasCondition) {
                    $ownershipQuery->whereRaw('1 = 0');
                }

                return;
            }

            if (!empty($access['user_id'])) {
                $ownershipQuery->where("{$alias}.requester_user_id", $access['user_id']);
                $hasCondition = true;
            }

            if (!empty($access['email'])) {
                if ($hasCondition) {
                    $ownershipQuery->orWhereRaw("LOWER({$alias}.requester_email) = ?", [$access['email']]);
                } else {
                    $ownershipQuery->whereRaw("LOWER({$alias}.requester_email) = ?", [$access['email']]);
                    $hasCondition = true;
                }
            }

            if (!$hasCondition) {
                $ownershipQuery->whereRaw('1 = 0');
            }
        });
    }

    private function calculateTotalPaidUsd(array $access): float
    {
        if (Schema::hasTable('royalty_payout_items') && Schema::hasTable('royalty_allocations')) {
            $itemsBase = DB::table('royalty_payout_items as rpi')
                ->join('royalty_allocations as ra', 'ra.id', '=', 'rpi.royalty_allocation_id');

            $this->applyAllocationOwnershipFilter($itemsBase, $access, 'ra');

            return (float) ((clone $itemsBase)->sum('rpi.amount_usd') ?? 0);
        }

        if (Schema::hasTable('royalty_payout_payments') && Schema::hasTable('royalty_payout_requests')) {
            $paymentsBase = DB::table('royalty_payout_payments as rpp')
                ->join('royalty_payout_requests as rpr', 'rpr.id', '=', 'rpp.royalty_payout_request_id');

            $this->applyPayoutOwnershipFilter($paymentsBase, $access, 'rpr');

            return (float) ((clone $paymentsBase)->sum('rpp.amount_usd') ?? 0);
        }

        return 0.0;
    }

    private function allocatePaymentToAllocations(
        RoyaltyPayoutRequest $payoutRequest,
        RoyaltyPayoutPayment $payment,
        float $paymentAmount
    ): void {
        $access = $this->buildAccessFromPayoutRequest($payoutRequest);

        $query = DB::table('royalty_allocations as ra')
            ->leftJoin('royalty_payout_items as rpi', 'rpi.royalty_allocation_id', '=', 'ra.id');

        $this->applyAllocationOwnershipFilter($query, $access, 'ra');

        $allocations = $query
            ->groupBy('ra.id', 'ra.allocated_amount_usd', 'ra.activity_month_date')
            ->selectRaw('
                ra.id,
                ra.allocated_amount_usd,
                ra.activity_month_date,
                COALESCE(SUM(rpi.amount_usd),0) as paid_amount_usd
            ')
            ->havingRaw('(ra.allocated_amount_usd - COALESCE(SUM(rpi.amount_usd),0)) > 0.000001')
            ->orderByRaw('CASE WHEN ra.activity_month_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('ra.activity_month_date')
            ->orderBy('ra.id')
            ->get();

        if ($allocations->isEmpty()) {
            throw ValidationException::withMessages([
                'amount_usd' => 'No hay allocations pendientes para aplicar este pago.',
            ]);
        }

        $remaining = $paymentAmount;
        $rows = [];
        $now = now();

        foreach ($allocations as $allocation) {
            if ($remaining <= 0.000001) {
                break;
            }

            $allocationRemaining = round(
                (float) $allocation->allocated_amount_usd - (float) $allocation->paid_amount_usd,
                6
            );

            if ($allocationRemaining <= 0.000001) {
                continue;
            }

            $applyAmount = round(min($remaining, $allocationRemaining), 6);
            if ($applyAmount <= 0) {
                continue;
            }

            $rows[] = [
                'royalty_payout_payment_id' => $payment->id,
                'royalty_allocation_id' => (int) $allocation->id,
                'amount_usd' => number_format($applyAmount, 6, '.', ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $remaining = round($remaining - $applyAmount, 6);
        }

        if ($remaining > 0.000001) {
            throw ValidationException::withMessages([
                'amount_usd' => 'No hay saldo suficiente en allocations para cubrir este pago.',
            ]);
        }

        RoyaltyPayoutItem::query()->insert($rows);
    }

    private function buildAccessFromPayoutRequest(RoyaltyPayoutRequest $payoutRequest): array
    {
        $accessType = $payoutRequest->requester_artist_id ? 'internal' : 'external';
        $metadataType = strtolower((string) data_get($payoutRequest->metadata, 'access_type', ''));
        if (in_array($metadataType, ['internal', 'external'], true)) {
            $accessType = $metadataType;
        }

        return [
            'type' => $accessType,
            'artist_id' => $payoutRequest->requester_artist_id,
            'user_id' => $payoutRequest->requester_user_id,
            'email' => strtolower((string) $payoutRequest->requester_email),
            'name' => $payoutRequest->requester_name,
        ];
    }

    private function resolveOpenRequestStatus(array $access): ?string
    {
        if (!Schema::hasTable('royalty_payout_requests')) {
            return null;
        }

        $query = DB::table('royalty_payout_requests as rpr')
            ->whereIn('rpr.status', ['pending', 'approved'])
            ->orderByDesc('rpr.requested_at')
            ->orderByDesc('rpr.id');

        $this->applyPayoutOwnershipFilter($query, $access, 'rpr');

        $status = $query->value('rpr.status');
        return $status ? (string) $status : null;
    }

    private function emptyOverview(): array
    {
        return [
            'total_accrued_usd' => 0.0,
            'total_paid_usd' => 0.0,
            'pending_requested_usd' => 0.0,
            'available_to_request_usd' => 0.0,
            'minimum_threshold_usd' => 50.0,
            'can_request_payment' => false,
            'open_requests_count' => 0,
            'period_totals' => collect(),
            'last_request' => null,
        ];
    }
}
