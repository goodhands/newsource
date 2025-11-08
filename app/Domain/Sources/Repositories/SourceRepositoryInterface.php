<?php

namespace App\Domain\Sources\Repositories;

use Illuminate\Support\Collection;
use App\Domain\Sources\Models\Source;

interface SourceRepositoryInterface
{
    /**
     * Get all active sources from the database
     *
     * @return Collection<int, Source>
     */
    public function getActiveSources(): Collection;
}
