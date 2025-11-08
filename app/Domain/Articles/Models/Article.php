<?php

namespace App\Domain\Articles\Models;

use App\Domain\Categories\Models\Category;
use App\Domain\Sources\Models\Source;
use App\Domain\Tags\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\Authors\Models\Author;
use App\Domain\Media\Models\Media;

class Article extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'external_url',
        'source_id',
        'description',
        'content',
        'published_at'
    ];

    /**
     * Define an inverse one-to-one relationship
     *
     * @return BelongsTo
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Define an inverse many-to-many relationship
     *
     * @return BelongsToMany
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function media(): belongsToMany
    {
        return $this->belongsToMany(Media::class);
    }

    public function tags(): belongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function categories(): belongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
