<?php

namespace App\Domain\Authors\Models;

use App\Domain\Articles\Models\Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    protected $fillable = [
        'firstname',
        'lastname',
        'profile_url',
        'bio',
        'source_id'
    ];

    /**
     * Define a many-to-many relationship with Article
     *
     * @return BelongsToMany
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }
}
