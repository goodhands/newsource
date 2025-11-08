<?php

namespace App\Domain\Articles\Repositories;

use App\Domain\Articles\Models\Article;
use Illuminate\Support\Collection;

interface ArticleRepositoryInterface
{
    /**
     * Get a single article by ID
     *
     * @param int $id
     * @return Article
     */
    public function getById(int $id): Article;

    /**
     * Save articles to the database
     *
     * @param array $articles
     * @return Collection<Article>
     */
    public function persist(array $articles): Collection;
}
