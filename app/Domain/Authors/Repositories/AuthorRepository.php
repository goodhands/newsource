<?php

namespace App\Domain\Authors\Repositories;

use App\Domain\Articles\Models\Article;
use App\Domain\Authors\Models\Author;

class AuthorRepository implements AuthorRepositoryInterface
{
    public function __construct()
    {
    }

    public function persist(array $authors): array
    {
        return collect($authors)->map(function ($author) {
            return Author::firstOrCreate($author)->id;
        })->toArray();
    }

    public function getRecentArticlesBySource(string $source): array
    {
        return Article::where('source', $source)
                        ->orderBy('id', 'desc')
                        ->get();
    }
}
