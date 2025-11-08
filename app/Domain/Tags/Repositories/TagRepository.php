<?php

namespace App\Domain\Tags\Repositories;

use App\Domain\Tags\Models\Tag;

class TagRepository implements TagRepositoryInterface
{
    public function __construct()
    {
    }

    public function getRecentArticlesBySource(string $source): array
    {
        return Article::where('source', $source)
                        ->orderBy('id', 'desc')
                        ->get();
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
}
