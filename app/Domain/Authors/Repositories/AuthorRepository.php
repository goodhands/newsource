<?php

namespace App\Domain\Authors\Repositories;

use App\Domain\Authors\Models\Author;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuthorRepository implements AuthorRepositoryInterface
{
    public function __construct()
    {
    }

    public function persist(array $authors): array
    {
        return collect($authors)->map(function ($author) {
            return Author::firstOrCreate($author)->id;
        })->toArray();
    }

    public function all(): LengthAwarePaginator
    {
        return Author::orderBy('id', 'desc')->paginate();
    }
}
