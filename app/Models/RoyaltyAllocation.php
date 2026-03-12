<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoyaltyAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'royalty_statement_id',
        'royalty_statement_line_id',
        'track_id',
        'right_type',
        'composition_id',
        'track_split_agreement_id',
        'composition_split_agreement_id',
        'composition_split_set_id',
        'track_split_participant_id',
        'composition_split_participant_id',
        'party_user_id',
        'party_artist_id',
        'party_email',
        'role',
        'activity_month_date',
        'split_percentage',
        'gross_amount_usd',
        'allocated_amount_usd',
        'currency',
        'status',
    ];

    protected $casts = [
        'activity_month_date' => 'date',
        'split_percentage' => 'decimal:4',
        'gross_amount_usd' => 'decimal:6',
        'allocated_amount_usd' => 'decimal:6',
    ];

    public function statement()
    {
        return $this->belongsTo(RoyaltyStatement::class, 'royalty_statement_id');
    }

    public function statementLine()
    {
        return $this->belongsTo(RoyaltyStatementLine::class, 'royalty_statement_line_id');
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function composition()
    {
        return $this->belongsTo(Composition::class);
    }

    public function splitAgreement()
    {
        return $this->belongsTo(TrackSplitAgreement::class, 'track_split_agreement_id');
    }

    public function compositionSplitAgreement()
    {
        return $this->belongsTo(CompositionSplitAgreement::class, 'composition_split_agreement_id');
    }

    public function compositionSplitSet()
    {
        return $this->belongsTo(CompositionSplitSet::class, 'composition_split_set_id');
    }

    public function splitParticipant()
    {
        return $this->belongsTo(TrackSplitParticipant::class, 'track_split_participant_id');
    }

    public function compositionSplitParticipant()
    {
        return $this->belongsTo(CompositionSplitParticipant::class, 'composition_split_participant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'party_user_id');
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class, 'party_artist_id');
    }

    public function payoutItems()
    {
        return $this->hasMany(RoyaltyPayoutItem::class, 'royalty_allocation_id');
    }
}
