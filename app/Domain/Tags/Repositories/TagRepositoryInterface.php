<?php

namespace App\Domain\Tags\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TagRepositoryInterface
{
    public function persist(array $tags): array;
    public function all(): LengthAwarePaginator;
}
