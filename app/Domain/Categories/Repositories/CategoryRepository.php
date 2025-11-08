<?php

namespace App\Domain\Categories\Repositories;

use App\Domain\Articles\Models\Article;
use App\Domain\Categories\Models\Category;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct()
    {
    }

    /**
     * Create a new category
     *
     * @param array $categories
     * @return array
     */
    public function persist(array $categories): array
    {
        $category = Category::firstOrCreate($categories);

        $category->save();

        return $category->toArray();
    }
}
