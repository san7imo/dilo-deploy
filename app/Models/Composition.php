<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Composition extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    protected $fillable = [
        'title',
        'iswc',
        'notes',
    ];

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'recording_compositions')
            ->withTimestamps();
    }

    public function recordingCompositions(): HasMany
    {
        return $this->hasMany(RecordingComposition::class);
    }

    public function splitAgreements(): HasMany
    {
        return $this->splitSets();
    }

    public function splitSets(): HasMany
    {
        return $this->hasMany(CompositionSplitSet::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CompositionRegistration::class);
    }

    public function royaltyLines(): HasMany
    {
        return $this->hasMany(CompositionRoyaltyLine::class);
    }

    public function royaltyAllocations(): HasMany
    {
        return $this->hasMany(CompositionAllocation::class);
    }
}
