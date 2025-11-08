<?php

namespace App\Domain\Articles\Repositories;

use App\Domain\Articles\Models\Article;
use App\Domain\Authors\Repositories\AuthorRepositoryInterface;
use App\Domain\Categories\Repositories\CategoryRepositoryInterface;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Tags\Repositories\TagRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        protected AuthorRepositoryInterface $authorRepository,
        protected MediaRepositoryInterface $mediaRepository,
        protected CategoryRepositoryInterface $categoryRepository,
        protected TagRepositoryInterface $tagRepository
    )
    {

    }
    public function getRecentArticlesBySource(string $source): array
    {
        return Article::where('source', $source)
                        ->orderBy('id', 'desc')
                        ->get();
    }

    /**
     * Get all articles with search, filters, and eager loading
     *
     * @param string|null $search
     * @param array $filters
     * @param array $include
     * @param int $perPage
     * @param User|null $user
     * @return LengthAwarePaginator
     */
    public function all(?string $search = null, array $filters = [], array $include = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        $query = Article::query();

        if ($user && $user->preferences) {
            $this->applyUserPreferences($query, $user->preferences);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $this->applyFilters($query, $filters);

        $this->applyIncludes($query, $include);

        $query->orderBy('published_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Apply filters to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['date_from'])) {
            $query->whereDate('published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('published_at', '<=', $filters['date_to']);
        }

        if (isset($filters['source'])) {
            $query->whereHas('source', function ($q) use ($filters) {
                if (is_numeric($filters['source'])) {
                    $q->where('id', $filters['source']);
                } else {
                    $q->where('name', 'like', "%{$filters['source']}%");
                }
            });
        }

        if (isset($filters['category'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('slug', $filters['category'])
                  ->orWhere('name', 'like', "%{$filters['category']}%");
            });
        }

        if (isset($filters['author'])) {
            $query->whereHas('authors', function ($q) use ($filters) {
                $q->where('firstname', 'like', "%{$filters['author']}%")
                  ->orWhere('lastname', 'like', "%{$filters['author']}%");
            });
        }

        if (isset($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('slug', $filters['tag'])
                  ->orWhere('name', 'like', "%{$filters['tag']}%");
            });
        }
    }

    /**
     * Apply eager loading to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $include
     * @return void
     */
    private function applyIncludes($query, array $include): void
    {
        $validRelationships = ['source', 'authors', 'tags', 'categories', 'media'];
        $loadRelationships = array_intersect($include, $validRelationships);

        if (!empty($loadRelationships)) {
            $query->with($loadRelationships);
        }
    }

    /**
     * Apply user preferences to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $preferences
     * @return void
     */
    private function applyUserPreferences($query, array $preferences): void
    {
        if (isset($preferences['sources']) && !empty($preferences['sources'])) {
            $query->whereIn('source_id', $preferences['sources']);
        }

        if (isset($preferences['categories']) && !empty($preferences['categories'])) {
            $query->whereHas('categories', function ($q) use ($preferences) {
                $q->whereIn('categories.id', $preferences['categories']);
            });
        }

        if (isset($preferences['authors']) && !empty($preferences['authors'])) {
            $query->whereHas('authors', function ($q) use ($preferences) {
                $q->whereIn('authors.id', $preferences['authors']);
            });
        }
    }

    public function persist(array $articles): Collection
    {
        return collect($articles)->map(fn ($article) => $this->persistSingleArticle($article));
    }

    private function persistSingleArticle(array $articleData): Article
    {
        $related = $this->extractRelationships($articleData);

        $article = $this->create($articleData);

        $this->attachRelationships($article, $related);

        return $article;
    }

    /**
     * Extract related data from article array
     */
    private function extractRelationships(array &$article): array
    {
        return [
            'authors' => Arr::pull($article, 'authors', []),
            'media' => Arr::pull($article, 'media'),
            'categories' => Arr::pull($article, 'categories', []),
            'tags' => Arr::pull($article, 'tags', []),
        ];
    }

    /**
     * Attach relationships to article
     */
    private function attachRelationships(Article $article, array $related): void
    {
        if ($related['authors']) {
            $article->authors()->createMany($related['authors']);
        }

        if ($related['media']) {
            $article->media()->createMany($related['media']);
        }

        if ($related['categories']) {
            $categoryIds = $this->categoryRepository->persist($related['categories']);
            $article->categories()->sync($categoryIds);
        }

        if ($related['tags']) {
            $tagIds = $this->tagRepository->persist($related['tags']);
            $article->tags()->sync($tagIds);
        }
    }

    public function getById(int $id, $include = []): Article
    {
        $relationships = ['source', 'authors', 'tags', 'categories', 'media'];

        $loadRelationships = array_intersect($include, $relationships);

        return Article::with($loadRelationships)->findOrFail($id);
    }

    public function create(array $articleData): Article
    {
        $article = new Article($articleData);

        $article->save();

        return $article;
    }
}
