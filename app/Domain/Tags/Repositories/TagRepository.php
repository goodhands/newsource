<?php

namespace App\Domain\Tags\Repositories;

use App\Domain\Tags\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TagRepository implements TagRepositoryInterface
{
    public function __construct()
    {
    }

    /**
     * Create new tags
     *
     * @param array $tags
     *
     * @return array
     */
    public function persist(array $tags): array
    {
        $tagIds = [];
        foreach ($tags as $tagData) {
            $tag = Tag::firstOrCreate(
                ['name' => $tagData['name']],
                ['slug' => $tagData['slug']]
            );
            $tagIds[] = $tag->id;
        }
        return $tagIds;
    }

    /**
     * Get all tags
     *
     * @return LengthAwarePaginator
     */
    public function all(): LengthAwarePaginator
    {
        return Tag::orderBy('name', 'asc')->paginate();
    }
}
