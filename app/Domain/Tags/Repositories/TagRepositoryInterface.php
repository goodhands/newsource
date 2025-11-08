<?php

namespace App\Domain\Tags\Repositories;

interface TagRepositoryInterface
{
    public function persist(array $tags): array;
}
