<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_holder',
        'holder_id',
        'account_type',
        'bank',
        'address',
        'phone',
        'account_number',
        'country',
    ];
}
