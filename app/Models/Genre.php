<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Genre extends Model
{
    use HasFactory;

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
