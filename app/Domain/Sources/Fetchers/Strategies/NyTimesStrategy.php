<?php

namespace App\Domain\Sources\Fetchers\Strategies;

use App\Domain\Sources\Fetchers\FetcherStrategyInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Models\Fetch;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;

class NyTimesStrategy extends BaseStrategy implements FetcherStrategyInterface
{
    public const BASE_URL = "https://api.nytimes.com/svc/search/v2/articlesearch.json";
    public const RATE_LIMIT_PER_HOUR = 1000;
    public const ONE_HOUR_IN_MILLISECOND = 3600 * 1000;

    /**
     * @throws ConnectionException
     */
    public function fetchArticles(): array
    {
        $config = $this->getFetchConfig('nytimes');

        Log::debug("NYTimes config " . print_r($config, true));

        $retryAfter = $config['retryAfter'];
        $nextPage = $config['nextPage'];
        $sourceId = $config['sourceId'];

        $response = Http::retry(3, $retryAfter)
                            ->get(self::BASE_URL, [
                                'api-key' => env('NYTIMES_API_KEY'),
                                'page' => $nextPage
                            ]);

        Log::debug("Response status is " . $response->status());

        if (!$response->ok()) {
            Log::debug('A non okay response was received, see the headers ' . print_r($response->headers(), true));
        }

        $data = $response->json();

        $articles = $data['response']['docs'];

        return array_map(fn ($article) => [
            'title' => $article['headline']['main'],
            'slug' => Str::slug($article['headline']['main']),
            'tags' => $this->processTags($article['keywords']),
            'authors' => $this->processAuthors($article['byline']['original'], $sourceId),
            'media' => $this->processMedia($article['multimedia']),
            'description' => $article['abstract'],
            'content' => null,
            'categories' => $this->processCategory($article['type_of_material'] ?? $article['section_name']),
            'external_url' => $article['web_url'],
            'source_id' => $sourceId,
            'published_at' => $article['pub_date'],
        ], $articles);
    }

    private function processTags(array $tags): array
    {
        return array_map(fn ($tag) => [
            'name' => $tag['value'],
            'slug' => Str::slug($tag['value']),
        ], $tags);
    }

    protected function processAuthors(string $author, $sourceId): array
    {
        $normalized = trim($author, "By");
        $normalized = str_replace("and", ",", $normalized);
        $normalized = explode(",", $normalized);

        return array_map(function ($author) use ($sourceId) {
            $names = explode(" ", $author);

            $firstname = isset($names[0]) ? trim($names[0]) : '';
            $lastname = isset($names[1]) ? trim($names[1]) : '';

            return [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'source_id' => $sourceId
            ];
        }, $normalized);
    }

    private function processMedia(mixed $elements): array
    {
        $media = [];

        if (empty($elements) || !is_array($elements)) {
            return $media;
        }

        $url = $elements['default']['url'] ?? null;
        $caption = $elements['caption'] ?? null;

        if ($url) {
            $media[] = [
                'url' => $url,
                'alt' => $caption
            ];
        }

        return $media;
    }

    private function processCategory(mixed $category): array
    {
        return array(
            'name' => $category,
            'slug' => Str::slug($category),
        );
    }
}
