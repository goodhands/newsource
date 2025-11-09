<?php

namespace App\Domain\Authors\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuthorRepositoryInterface
{
    public function persist(array $authors): array;
    public function all(): LengthAwarePaginator;
}
