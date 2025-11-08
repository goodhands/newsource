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
     * @param array $include
     * @return Article
     */
    public function getById(int $id, array $include = []): Article;

    /**
     * Save articles to the database
     *
     * @param array $articles
     * @return Collection<Article>
     */
    public function persist(array $articles): Collection;

    /**
     * Get all articles, optionally filtered by criteria
     *
     * @param array $filters
     *
     * @return mixed
     */
    public function all(array $filters = []);
}
