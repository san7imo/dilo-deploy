<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use App\Traits\SoftDeletesUniqueValues;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; 

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use LogsAuditTrail;
    use Notifiable;
    use SoftDeletes;
    use SoftDeletesUniqueValues;
    use TwoFactorAuthenticatable;
    use HasRoles; // Habilita roles y permisos (Spatie)

    protected array $softDeleteUniqueColumns = ['email', 'identification_number'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'stage_name',
        'email',
        'phone',
        'identification_number',
        'identification_type',
        'additional_information',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_unique_snapshot' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function artist()
    {
        return $this->hasOne(Artist::class);
    }

    public function roadManagedEvents()
    {
        return $this->belongsToMany(Event::class, 'event_road_manager')
            ->withPivot('payment_confirmed_at')
            ->withTimestamps();
    }

    public function splitParticipants()
    {
        return $this->hasMany(TrackSplitParticipant::class);
    }

    public function externalArtistInvitationsAccepted()
    {
        return $this->hasMany(ExternalArtistInvitation::class, 'accepted_user_id');
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
