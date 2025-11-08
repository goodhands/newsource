<?php

namespace App\Domain\Articles\Repositories;

use App\Domain\Articles\Models\Article;
use App\Domain\Authors\Repositories\AuthorRepositoryInterface;
use App\Domain\Categories\Repositories\CategoryRepositoryInterface;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Tags\Repositories\TagRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
     * Search the articles collection
     *
     * @param string $keyword
     * @return array
     */
    public function search(string $keyword): array
    {
//        TODO:  Improve search query
        return Article::where('title', 'like', '%' . $keyword . '%')->get();
    }

    public function filter(array $filters): array
    {
//        TODO: Implement filter
//        can filter by single category or array of categories
//        can filter by date
//        can filter by author
    }

    public function all(array $filters = []): array
    {
//        TODO: Implement filter
        if (empty($filters)) {
            return Article::all();
        }

        return Article::where($filters)->get();
    }

    public function persist(array $articles): Collection
    {
        Log::info("Persisting " . count($articles) . " articles");
        return collect($articles)->map(fn ($article) => $this->persistSingleArticle($article));
    }

    private function persistSingleArticle(array $articleData): Article
    {
        $related = $this->extractRelationships($articleData);

        $article = $this->create($articleData);

        $this->attachRelationships($article, $related);

//        Maybe dispatch event here

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
            Log::debug("media is " . print_r($related['media'], true));
            $article->media()->createMany($related['media']);
        }

        if ($related['categories']) {
            $article->categories()->create($related['categories']);
        }

        if ($related['tags']) {
            $article->tags()->createMany($related['tags']);
        }
    }

    public function getById(int $id): Article
    {
        // TODO: Implement getById() method.
    }

    public function create(array $articleData): Article
    {
        $article = new Article($articleData);

        $article->save();

        return $article;
    }
}
