<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worker extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    protected $fillable = [
        'full_name',
        'document_type',
        'document_number',
        'position',
        'address',
        'bank_name',
        'account_number',
        'whatsapp',
        'email',
        'notes',
    ];

    public function payrollPayments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }
}
