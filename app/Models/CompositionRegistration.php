<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompositionRegistration extends Model
{
    use HasFactory, LogsAuditTrail;

    protected $fillable = [
        'composition_id',
        'registration_type',
        'society_name',
        'registration_number',
        'territory_code',
        'status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function composition()
    {
        return $this->belongsTo(Composition::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

