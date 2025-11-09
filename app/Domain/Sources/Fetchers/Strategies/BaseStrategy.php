<?php

namespace App\Domain\Sources\Fetchers\Strategies;

use App\Domain\Fetches\Repositories\FetchRepository;
use App\Domain\Fetches\Repositories\FetchRepositoryInterface;
use App\Domain\Sources\Models\Source;
use App\Models\Fetch;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class BaseStrategy
{
    public const ONE_HOUR_IN_MILLISECOND = 3600 * 1000;
    protected FetchRepositoryInterface $fetchRepository;

    public function __construct()
    {
        $this->fetchRepository = new FetchRepository();
    }

    public function getFetchConfig($sourceName): array
    {
        $source = Source::where('name', $sourceName)->first();

        if (!$source) {
            Log::debug("Source not found for source name $sourceName");
            return array(
                'retryAfter' => self::ONE_HOUR_IN_MILLISECOND,
                'nextPage' => 1,
                'sourceId' => null,
                'shouldSkip' => false,
            );
        }

        $lastFetch = $this->fetchRepository->getLatestFetchForSource($source->id);

        if ($this->fetchRepository->shouldResetPagination($source->id)) {
            return array(
                'retryAfter' => self::ONE_HOUR_IN_MILLISECOND,
                'nextPage' => 1,
                'sourceId' => $source->id,
                'shouldSkip' => false,
            );
        }

        if ($lastFetch && $lastFetch->exists()) {
            $pagesFetched = $lastFetch->pages_fetched;
            $retryAfter = $lastFetch->retry_after_seconds === 0 ? self::ONE_HOUR_IN_MILLISECOND : $lastFetch->retry_after_seconds;
            $wasRateLimited = $lastFetch->was_rate_limited;

            if ($wasRateLimited) {
                Log::warning("Source {$sourceName} is rate limited. Skipping fetch.");
                return array(
                    'retryAfter' => $retryAfter,
                    'nextPage' => $pagesFetched,
                    'sourceId' => $source->id,
                    'shouldSkip' => true,
                );
            }

            $nextPage = $pagesFetched + 1;
        } else {
            $nextPage = 1;
            $retryAfter = self::ONE_HOUR_IN_MILLISECOND;
        }

        return array(
            'retryAfter' => $retryAfter,
            'nextPage' => $nextPage,
            'sourceId' => $source->id,
            'shouldSkip' => false,
        );
    }

    /**
     * Extract rate limit information from response headers
     *
     * @param Response $response
     * @return array
     */
    protected function extractRateLimitInfo(Response $response): array
    {
        $headers = $response->headers();

        $remainingDay = $headers['X-RateLimit-Remaining-Day'][0] ?? null;
        $limitDay = $headers['X-RateLimit-Limit-Day'][0] ?? null;
        $remainingMinute = $headers['X-RateLimit-Remaining-Minute'][0] ?? null;
        $limitMinute = $headers['X-RateLimit-Limit-Minute'][0] ?? null;

        $remaining = $headers['X-RateLimit-Remaining'][0] ?? null;
        $limit = $headers['X-RateLimit-Limit'][0] ?? null;
        $reset = $headers['X-RateLimit-Reset'][0] ?? null;

        $retryAfter = $headers['Retry-After'][0] ?? null;

        return [
            'remaining_day' => $remainingDay,
            'limit_day' => $limitDay,
            'remaining_minute' => $remainingMinute,
            'limit_minute' => $limitMinute,
            'remaining' => $remaining,
            'limit' => $limit,
            'reset' => $reset,
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * Save fetch result to database
     *
     * @param int $sourceId
     * @param Response $response
     * @param int $articlesCount
     * @param int $currentPage
     * @param int $totalPages
     * @return Fetch
     */
    protected function saveFetchResult(
        int $sourceId,
        Response $response,
        int $articlesCount,
        int $currentPage,
        int $totalPages = 0
    ): Fetch {
        $rateLimitInfo = $this->extractRateLimitInfo($response);
        $statusCode = $response->status();
        $wasRateLimited = $statusCode === 429;

        $retryAfterSeconds = 0;
        if ($wasRateLimited) {
            $retryAfterSeconds = $rateLimitInfo['retry_after'] ?? 3600;
        }

        $fetchData = [
            'source_id' => $sourceId,
            'pages_fetched' => $currentPage,
            'articles_fetched' => $articlesCount,
            'total_pages_available' => $totalPages,
            'http_status_code' => $statusCode,
            'error_message' => !$response->successful() ? $response->body() : null,
            'was_rate_limited' => $wasRateLimited,
            'retry_after_seconds' => $retryAfterSeconds,
        ];

        return $this->fetchRepository->saveFetch($fetchData);
    }
}
