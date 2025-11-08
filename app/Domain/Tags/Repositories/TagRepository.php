<?php

namespace App\Domain\Tags\Repositories;

use App\Domain\Articles\Models\Article;

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
}
