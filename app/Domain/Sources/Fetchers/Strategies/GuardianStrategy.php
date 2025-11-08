<?php

namespace App\Domain\Sources\Fetchers\Strategies;

use App\Domain\Sources\Fetchers\FetcherStrategyInterface;
use App\Models\Fetch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuardianStrategy extends BaseStrategy implements FetcherStrategyInterface
{
    public const BASE_URL = "https://content.guardianapis.com/search?show-fields=body,trailText&show-tags=contributor,keyword";
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

        $response = Http::retry(3, $retryAfter)
                        ->get(self::BASE_URL, [
                            'api-key' => env('GUARDIAN_API_KEY'),
                            'page' => $nextPage
                        ]);

        if (!$response->ok()) {
            Log::debug('A non okay response was received, see the headers ' . print_r($response->headers(), true));
        }

        $data = $response->json();

        $articles = $data['response']['results'];

        return array_map(fn ($article) => [
            'title' => $article['webTitle'],
            'slug' => Str::slug($article['webTitle']),
            'tags' => $this->processTags($article['tags']),
            'authors' => $this->processAuthors($article['tags'], $sourceId),
            'media' => $this->processMedia($article['elements']),
            'description' => $article['fields']['trailText'],
            'content' => $article['fields']['body'],
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

        return array_map(fn ($tag) => [
            'firstname' => explode(' ', $tag['webTitle'])[0],
            'lastname' => explode(' ', $tag['webTitle'])[0],
            'bio' => trim(strip_tags($tag['bio'])),
            'profile_url' => $tag['bylineImageUrl'],
            'source_id' => $sourceId,
        ], $tags);
    }

    private function processMedia(array $elements): array
    {
        $media = [];

        foreach ($elements as $element) {
            $asset = $element['assets'];
            $media[] = array('url' => $asset['file'], 'alt' => $asset['typeData']['altText']);
        }

        return $media;
    }
}
