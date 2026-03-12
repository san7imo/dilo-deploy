<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organizer extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes;

    protected $fillable = [
        'company_name',
        'contact_name',
        'address',
        'whatsapp',
        'email',
        'logo_url',
        'website',
        'instagram_url',
        'facebook_url',
        'tiktok_url',
        'x_url',
        'notes',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
