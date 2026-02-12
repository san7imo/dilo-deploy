<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackSplitAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'track_id',
        'split_type',
        'status',
        'effective_from',
        'effective_to',
        'contract_path',
        'contract_original_filename',
        'contract_hash',
        'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function participants()
    {
        return $this->hasMany(TrackSplitParticipant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
