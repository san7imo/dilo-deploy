<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackSplitParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'track_split_agreement_id',
        'artist_id',
        'payee_email',
        'name',
        'role',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function agreement()
    {
        return $this->belongsTo(TrackSplitAgreement::class, 'track_split_agreement_id');
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}
