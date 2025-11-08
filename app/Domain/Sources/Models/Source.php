<?php

namespace App\Domain\Sources\Models;

use App\Domain\Articles\Models\Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $fillable = [
        'name',
        'url'
    ];

    /**
     * Define a one-to-many relationship with Article
     *
     * @return HasMany
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
