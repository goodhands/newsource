<?php

namespace App\Domain\Articles\Services;

use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Domain\Sources\Fetchers\FetcherFactory;
use App\Domain\Sources\Repositories\SourceRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ArticleAggregatorService
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
        protected SourceRepositoryInterface $sourceRepository
    )
    {
    }

    public function aggregateLatestArticles()
    {
        Log::info("Fetching all active sources");
        $sources = $this->sourceRepository->getActiveSources();

        foreach ($sources as $source) {
            Log::info("Processing source {$source}");
            $this->fetchArticles($source->name);
        }

        return true;
    }

    public function fetchArticles($sourceName)
    {
        $sourceName = strtolower($sourceName);
        Log::info("Fetching articles with {$sourceName} strategy");
        $fetcherStrategy = FetcherFactory::create($sourceName);
        $articles = $fetcherStrategy->fetchArticles();

        $total = count($articles);

        Log::debug("We got {$total} articles from {$sourceName}");

        $this->articleRepository->persist($articles);

        return $articles;
    }
}
