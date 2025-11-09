<?php

namespace App\Domain\Fetches\Repositories;

use App\Models\Fetch;

class FetchRepository implements FetchRepositoryInterface
{
    /**
     * Save a fetch attempt
     *
     * @param array $fetchData
     * @return Fetch
     */
    public function saveFetch(array $fetchData): Fetch
    {
        return Fetch::create($fetchData);
    }

    /**
     * Get the latest fetch for a source
     *
     * @param int $sourceId
     * @return Fetch|null
     */
    public function getLatestFetchForSource(int $sourceId): ?Fetch
    {
        return Fetch::forSource($sourceId)
            ->latest()
            ->first();
    }

    /**
     * Check if the last fetch completed pagination (no more pages or got 404)
     *
     * @param int $sourceId
     * @return bool
     */
    public function shouldResetPagination(int $sourceId): bool
    {
        $lastFetch = $this->getLatestFetchForSource($sourceId);

        if (!$lastFetch) {
            return false;
        }

        // Reset if we got a 404 or empty results (articles_fetched = 0)
        if ($lastFetch->http_status_code === 404 || $lastFetch->articles_fetched === 0) {
            return true;
        }

        // Reset if we've reached the end of pagination
        // (currentPage >= totalPages and totalPages is set)
        if ($lastFetch->total_pages_available > 0 &&
            $lastFetch->pages_fetched >= $lastFetch->total_pages_available) {
            return true;
        }

        return false;
    }
}
