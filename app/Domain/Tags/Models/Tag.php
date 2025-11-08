<?php

namespace App\Domain\Tags\Models;

use App\Domain\Articles\Models\Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'slug',
        'name',
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
