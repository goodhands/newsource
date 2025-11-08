<?php

namespace App\Domain\Articles\Repositories;

use App\Domain\Articles\Models\Article;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * Get all articles with search, filters, and eager loading
     *
     * @param string|null $search Search term for title, description, content
     * @param array $filters Filtering criteria (date_from, date_to, source, category, author, tag)
     * @param array $include Relationships to eager load (source, authors, categories, tags, media)
     * @param int $perPage Number of items per page
     * @param User|null $user User for applying preference-based filtering
     * @return LengthAwarePaginator
     */
    public function all(?string $search = null, array $filters = [], array $include = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator;
}
