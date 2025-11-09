<?php

namespace App\Domain\Fetches\Repositories;

use App\Models\Fetch;

interface FetchRepositoryInterface
{
    /**
     * Save a fetch attempt
     *
     * @param array $fetchData
     * @return Fetch
     */
    public function saveFetch(array $fetchData): Fetch;

    /**
     * Get the latest fetch for a source
     *
     * @param int $sourceId
     * @return Fetch|null
     */
    public function getLatestFetchForSource(int $sourceId): ?Fetch;

    /**
     * Check if the last fetch completed pagination (no more pages or got 404)
     *
     * @param int $sourceId
     * @return bool
     */
    public function shouldResetPagination(int $sourceId): bool;
}
