<?php

namespace App\Models;

use App\Traits\LogsAuditTrail;
use App\Traits\SoftDeletesUniqueValues;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Genre extends Model
{
    use HasFactory, LogsAuditTrail, SoftDeletes, SoftDeletesUniqueValues;

    protected array $softDeleteUniqueColumns = ['name', 'slug'];

    protected $casts = [
        'deleted_unique_snapshot' => 'array',
    ];

    protected $fillable = ['name', 'slug'];

    /**
     * Relación con lanzamientos (releases) asociados a este género.
     */
    public function releases()
    {
        return $this->belongsToMany(Release::class, 'genre_release');
    }

    /**
     * Genera automáticamente el slug al crear un nuevo género.
     */
    protected static function booted()
    {
        static::creating(function ($genre) {
            $genre->slug = Str::slug($genre->name);
        });
    }
}
