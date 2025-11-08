<?php

namespace App\Domain\Media\Models;

use App\Domain\Articles\Models\Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Media extends Model
{
    protected $fillable = [
        'url',
        'alt',
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
