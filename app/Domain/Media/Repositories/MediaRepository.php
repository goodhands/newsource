<?php

namespace App\Domain\Media\Repositories;

use App\Domain\Articles\Models\Article;
use App\Domain\Media\Models\Media;

class MediaRepository implements MediaRepositoryInterface
{
    public function __construct()
    {
    }

    public function getRecentArticlesBySource(string $source): array
    {
        return Article::where('source', $source)
                        ->orderBy('id', 'desc')
                        ->get();
    }

    public function persist(array $media)
    {
        return collect($media)->map(function ($file) {
            return Media::firstOrCreate($file)->id;
        })->toArray();
    }
}
