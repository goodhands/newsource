<?php

namespace App\Domain\Sources\Fetchers;

use App\Domain\Sources\Fetchers\Strategies\GuardianStrategy;
use App\Domain\Sources\Fetchers\Strategies\NewsApiStrategy;
use App\Domain\Sources\Fetchers\Strategies\NyTimesStrategy;

class FetcherFactory
{
    public static function create($sourceType): FetcherStrategyInterface
    {
        return match ($sourceType) {
            'newsapi' => new NewsApiStrategy(),
            'guardian' => new GuardianStrategy(),
            'nytimes' => new NytimesStrategy(),
            default => throw new \RuntimeException('Unknown source type ' . $sourceType),
        };
    }
}
