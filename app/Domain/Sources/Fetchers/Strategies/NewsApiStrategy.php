<?php

namespace App\Domain\Sources\Fetchers\Strategies;

use App\Domain\Sources\Fetchers\FetcherStrategyInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsApiStrategy extends BaseStrategy implements FetcherStrategyInterface
{
    public const BASE_URL = "https://newsapi.org/v2/everything?excludeDomains=theguardian.com,nytimes.com&domains=bbc.co.uk,punch.com,bbc.com,opennews.org&language=en";
    public const ONE_HOUR_IN_MILLISECOND = 3600 * 1000;

    /**
     * @throws ConnectionException
     */
    public function fetchArticles(): array
    {
        $config = $this->getFetchConfig('guardian');

        $retryAfter = $config['retryAfter'];
        $nextPage = $config['nextPage'];
        $sourceId = $config['sourceId'];

        $response = Http::retry(3, self::ONE_HOUR_IN_MILLISECOND)
            ->get(self::BASE_URL, [
                'apiKey' => env('NEWS_API_KEY'),
                'pageSize' => 10,
                'page' => $nextPage
            ]);

        if (!$response->ok()) {
            Log::debug('A non okay response was received, see the headers ' . print_r($response->headers(), true));
        }

        $data = $response->json();

        $articles = $data['response']['results'];

        return array_map(fn ($article) => [
            'title' => $article['title'],
            'slug' => Str::slug($article['webTitle']),
            'tags' => null,
            'authors' => $article['author'],
            'media' => $article['urlToImage'],
            'description' => $article['description'],
            'content' => $article['content'],
            'categories' => null,
            'external_url' => $article['url'],
            'source_id' => $sourceId,
            'published_at' => $article['publishedAt'],
        ], $articles);
    }
}
