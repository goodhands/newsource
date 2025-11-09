<?php

namespace App\Domain\Categories\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface
{
    public function persist(array $categories): array;
    public function all(): LengthAwarePaginator;
}
