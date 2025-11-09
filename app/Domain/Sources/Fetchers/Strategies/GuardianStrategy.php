<?php

namespace App\Domain\Sources\Fetchers\Strategies;

use App\Domain\Sources\Fetchers\FetcherStrategyInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuardianStrategy extends BaseStrategy implements FetcherStrategyInterface
{
    public const BASE_URL = "https://content.guardianapis.com/search";
    public const ONE_HOUR_IN_MILLISECOND = 3600 * 1000;

    /**
     * @throws ConnectionException
     */
    public function fetchArticles(): array
    {
        $config = $this->getFetchConfig('guardian');

        $nextPage = $config['nextPage'];
        $sourceId = $config['sourceId'];
        $shouldSkip = $config['shouldSkip'];

        if ($shouldSkip) {
            Log::warning('Guardian: Skipping fetch due to rate limiting');
            return [];
        }

        $apiKey = env('GUARDIAN_API_KEY');
        try {
            $response = Http::timeout(30)
                                ->retry(3, 1000)
                                ->get(self::BASE_URL, [
                                    'api-key' => $apiKey,
                                    'page' => $nextPage,
                                    'show-fields' => 'body,trailText,thumbnail',
                                    'show-elements' => 'image',
                                    'show-tags' => 'contributor,keyword'
                                ]);
        } catch (ConnectionException $e) {
            Log::error("Error fetching Guardian articles: " . $e->getMessage());
            return [];
        }

        $data = $response->json();
        $articles = [];
        $totalPages = 0;

        if ($response->ok() && isset($data['response']['results'])) {
            $articles = $data['response']['results'];

            $totalPages = $data['response']['pages'] ?? 0;
        } else {
            Log::debug('Guardian: Non-OK response received', [
                'status' => $response->status(),
                'headers' => $response->headers()
            ]);
        }

        $this->saveFetchResult(
            $sourceId,
            $response,
            count($articles),
            $nextPage,
            $totalPages
        );

        if (empty($articles)) {
            return [];
        }

        return array_map(fn ($article) => [
            'title' => $article['webTitle'],
            'slug' => Str::slug($article['webTitle']),
            'tags' => $this->processTags($article['tags'] ?? []),
            'authors' => $this->processAuthors($article['tags'] ?? [], $sourceId),
            'media' => $this->processMedia($article['elements'] ?? [], $article['fields'] ?? []),
            'description' => $article['fields']['trailText'] ?? '',
            'content' => $article['fields']['body'] ?? null,
            'categories' => array('name' => $article['sectionName'], 'slug' => Str::slug($article['sectionName'])),
            'external_url' => $article['webUrl'],
            'source_id' => $sourceId,
            'published_at' => $article['webPublicationDate'],
        ], $articles);
    }

    private function processTags(mixed $tags): array
    {
        $tags = array_filter($tags, function ($tag) {
            return $tag['type'] === 'keyword';
        });

        return array_map(fn ($tag) => [
            'name' => $tag['webTitle'],
            'slug' => Str::slug($tag['webTitle']),
        ], $tags);
    }

    private function processAuthors(array $tags, $sourceId): array
    {
        $tags = array_filter($tags, static function ($tag) {
            return $tag['type'] === 'contributor';
        });

        return array_map(function ($tag) use ($sourceId) {
            $names = isset($tag['webTitle']) ? explode(' ', $tag['webTitle']) : ['',''];
            $firstname = isset($names[0]) ? trim($names[0]) : '';
            $lastname = isset($names[1]) ? trim($names[1]) : '';
            $bio = isset($tag['bio']) ? trim(strip_tags($tag['bio'])) : '';
            $profileUrl = isset($tag['bylineImageUrl']) ? $tag['bylineImageUrl'] : null;
            return [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'bio' => $bio,
                'profile_url' => $profileUrl,
                'source_id' => $sourceId,
            ];
        }, $tags);
    }

    private function processMedia(array $elements, array $fields = []): array
    {
        $media = [];

        foreach ($elements as $element) {
            if ($element['type'] === 'image' && isset($element['assets'])) {
                foreach ($element['assets'] as $asset) {
                    if (isset($asset['file'])) {
                        $media[] = [
                            'url' => $asset['file'],
                            'alt' => $asset['typeData']['altText'] ?? ''
                        ];
                        break;
                    }
                }
                if (!empty($media)) {
                    break;
                }
            }
        }

        if (empty($media) && isset($fields['thumbnail'])) {
            $media[] = [
                'url' => $fields['thumbnail'],
                'alt' => ''
            ];
        }

        return $media;
    }
}
