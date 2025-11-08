<?php

namespace App\Domain\Sources\Repositories;

use App\Domain\Sources\Models\Source;
use Illuminate\Database\Eloquent\Collection;

class SourceRepository implements SourceRepositoryInterface
{
    public function __construct()
    {}

    /**
     * Add a new source
     *
     * @param array $source
     * @return mixed
     */
    public function add(array $source): Source
    {
        $source = Source::create($source);

        return $source->save();
    }

    /**
     * Get a source by id
     *
     * @param int $sourceId
     * @return mixed
     */
    public function getSourceById(int $sourceId): Source
    {
        return Source::find($sourceId);
    }

    /**
     * {inheritDoc}
     */
    public function getActiveSources(): Collection
    {
        return Source::all();
    }
}
