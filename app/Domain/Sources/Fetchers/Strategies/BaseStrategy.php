<?php

namespace App\Domain\Sources\Fetchers\Strategies;

use App\Domain\Sources\Models\Source;
use App\Models\Fetch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class BaseStrategy
{
    public const ONE_HOUR_IN_MILLISECOND = 3600 * 1000;

    public function getFetchConfig($sourceName): array
    {
        $source = Source::where('name', $sourceName)->first();

        Log::debug("Fetch config for source name $sourceName");
        Log::debug("Source found for source name $sourceName is " . print_r($source, true));

        if (!$source) {
            Log::debug("Source not found for source name $sourceName");
            return array(
                'retryAfter' => self::ONE_HOUR_IN_MILLISECOND,
                'nextPage' => 1,
                'sourceId' => null,
            );
        }

        $fetch = Fetch::where('source_id', $source->id)->latest()->first();

        if ($fetch && $fetch->exists()) {
            $totalPages = $fetch->total_pages_available;
            $pagesFetched = $fetch->pages_fetched;
            $retryAfter = $fetch->retry_after_seconds === 0 ? self::ONE_HOUR_IN_MILLISECOND : $fetch->retry_after_seconds;
            $articlesFetched = $fetch->articles_fetched;
            $httpStatusCode = $fetch->http_status_code;
            $errorMessage = $fetch->error_message;
            $wasRateLimited = $fetch->was_rate_limited;

            $shouldFetchMore = $totalPages > $pagesFetched;
            $nextPage = $pagesFetched + 1;
        } else {
            $nextPage = 1;
            $retryAfter = self::ONE_HOUR_IN_MILLISECOND;
        }

        return array(
            'retryAfter' => $retryAfter,
            'nextPage' => $nextPage,
            'sourceId' => $source->id,
        );
    }
}
