<?php

namespace App\Domain\Categories\Repositories;

interface CategoryRepositoryInterface
{
    public function persist(array $categories): array;
}
