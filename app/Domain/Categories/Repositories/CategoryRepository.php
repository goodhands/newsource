<?php

namespace App\Domain\Categories\Repositories;

use App\Domain\Articles\Models\Article;
use App\Domain\Categories\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct()
    {
    }

    /**
     * Create new categories
     *
     * @param array $categories
     * @return array
     */
    public function persist(array $category): array
    {
        Log::debug('Persisting category: ' . print_r($category, true));
        $category = Category::firstOrCreate(
            ['name' => $category['name']],
            ['slug' => $category['slug']]
        );

        return [$category->id];
    }

    /**
     * Get all categories
     *
     * @return LengthAwarePaginator
     */
    public function all(): LengthAwarePaginator
    {
        return Category::orderBy('name', 'asc')->paginate();
    }
}
