<?php

namespace App\Domain\Sources\Fetchers\Strategies;

use App\Domain\Sources\Fetchers\FetcherStrategyInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsApiStrategy extends BaseStrategy implements FetcherStrategyInterface
{
    public const BASE_URL = "https://newsapi.org/v2/everything";
    public const ONE_HOUR_IN_MILLISECOND = 3600 * 1000;

    /**
     * @throws ConnectionException
     */
    public function fetchArticles(): array
    {
        $config = $this->getFetchConfig('newsapi');
        $nextPage = $config['nextPage'];
        $sourceId = $config['sourceId'];

        $apiKey = env('NEWS_API_KEY');
        if (empty($apiKey)) {
            Log::error('NEWS_API_KEY environment variable is not set');
            return [];
        }

        $response = Http::timeout(30)
            ->retry(3, 1000)
            ->get(self::BASE_URL, [
                'apiKey' => $apiKey,
                'q' => 'technology OR science OR business',
                'excludeDomains' => 'theguardian.com,nytimes.com',
                'domains' => 'bbc.co.uk,bbc.com',
                'language' => 'en',
                'pageSize' => 10,
                'page' => $nextPage,
                'sortBy' => 'publishedAt'
            ]);

        if (!$response->ok()) {
            Log::error('NewsAPI request failed', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);
            return [];
        }

        $data = $response->json();

        if (!isset($data['articles']) || empty($data['articles'])) {
            return [];
        }

        $articles = $data['articles'];

        return array_map(fn ($article) => [
            'title' => $article['title'],
            'slug' => Str::slug($article['title']),
            'tags' => [],
            'authors' => $this->processAuthors($article['author'], $sourceId),
            'media' => $this->processMedia($article['urlToImage']),
            'description' => $article['description'],
            'content' => $article['content'],
            'categories' => $this->processCategories($article['source']['name'] ?? ''),
            'external_url' => $article['url'],
            'source_id' => $sourceId,
            'published_at' => $article['publishedAt'],
        ], $articles);
    }

    private function processAuthors(?string $author, $sourceId): array
    {
        if (empty($author)) {
            return [];
        }

        $names = explode(' ', trim($author));
        $firstname = isset($names[0]) ? trim($names[0]) : '';
        $lastname = isset($names[1]) ? trim($names[1]) : '';

        return [[
            'firstname' => $firstname,
            'lastname' => $lastname,
            'source_id' => $sourceId
        ]];
    }

    private function processMedia(?string $imageUrl): array
    {
        if (empty($imageUrl)) {
            return [];
        }

        return [[
            'url' => $imageUrl,
            'alt' => ''
        ]];
    }

    private function processCategories(string $sourceName): array
    {
        if (empty($sourceName)) {
            return [];
        }

        return [
            'name' => $sourceName,
            'slug' => Str::slug($sourceName)
        ];
    }
}
