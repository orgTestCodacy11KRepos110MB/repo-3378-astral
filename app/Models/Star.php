<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Star extends Model
{
    use HasFactory, Cachable;

    protected $fillable = [
        'repo_id',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected $attributes = [
        'meta' => '{"nameWithOwner": "", "url": "", "description": ""}',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            $model->tags()->detach();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function isOrphan(): bool
    {
        return ! (bool) $this->notes && ! $this->tags()->count();
    }

    public function removeAllTags()
    {
        $this->tags()->sync([]);
    }
}
