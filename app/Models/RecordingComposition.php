<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordingComposition extends Model
{
    use HasFactory, LogsAuditTrail;

    protected $fillable = [
        'track_id',
        'composition_id',
        'relationship_type',
        'source',
        'notes',
    ];

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function composition()
    {
        return $this->belongsTo(Composition::class);
    }
}

