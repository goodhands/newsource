<?php

namespace App\Providers;

use App\Domain\Categories\Repositories\CategoryRepository;
use App\Domain\Categories\Repositories\CategoryRepositoryInterface;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\Repositories\MediaRepository;
use App\Domain\Sources\Repositories\SourceRepository;
use App\Domain\Sources\Repositories\SourceRepositoryInterface;
use App\Domain\Articles\Repositories\ArticleRepository;
use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Domain\Authors\Repositories\AuthorRepository;
use App\Domain\Authors\Repositories\AuthorRepositoryInterface;
use App\Domain\Fetches\Repositories\FetchRepositoryInterface;
use App\Domain\Sources\Fetchers\Strategies\BaseStrategy;
use App\Domain\Tags\Repositories\TagRepository;
use App\Domain\Tags\Repositories\TagRepositoryInterface;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            ArticleRepositoryInterface::class,
            ArticleRepository::class
        );

        $this->app->bind(
            AuthorRepositoryInterface::class,
            AuthorRepository::class
        );

        $this->app->bind(
            SourceRepositoryInterface::class,
            SourceRepository::class
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );

        $this->app->bind(
            MediaRepositoryInterface::class,
            MediaRepository::class
        );

        $this->app->bind(
            TagRepositoryInterface::class,
            TagRepository::class
        );

        $this->app->bind(
            FetchRepositoryInterface::class,
            BaseStrategy::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
