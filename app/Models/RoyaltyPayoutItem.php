<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoyaltyPayoutItem extends Model
{
    use HasFactory, LogsAuditTrail;

    protected $fillable = [
        'royalty_payout_payment_id',
        'royalty_allocation_id',
        'amount_usd',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:6',
    ];

    public function payoutPayment()
    {
        return $this->belongsTo(RoyaltyPayoutPayment::class, 'royalty_payout_payment_id');
    }

    public function allocation()
    {
        return $this->belongsTo(RoyaltyAllocation::class, 'royalty_allocation_id');
    }
}
