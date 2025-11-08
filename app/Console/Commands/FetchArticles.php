<?php

namespace App\Console\Commands;

use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Domain\Sources\Fetchers\FetcherFactory;
use App\Domain\Sources\Repositories\SourceRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
        protected SourceRepositoryInterface $sourceRepository
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting article fetch process...');
        $startTime = microtime(true);

        try {
            $this->aggregateLatestArticles();

            $totalTime = round(microtime(true) - $startTime, 2);
            $this->info("Article fetch completed successfully in {$totalTime} seconds!");

        } catch (\Exception $e) {
            $this->error("Error during article fetch: " . $e->getMessage());
            Log::error("Error during article fetch", ['exception' => $e]);
            return 1;
        }

        return 0;
    }

    private function aggregateLatestArticles()
    {
        $this->info('Fetching active sources...');
        Log::info("Fetching all active sources");

        $sources = $this->sourceRepository->getActiveSources();
        $sourceCount = count($sources);

        $this->info("Found {$sourceCount} active sources");

        $totalArticles = 0;
        $successCount = 0;
        $failureCount = 0;

        foreach ($sources as $source) {
            $this->line('');
            $this->info("Processing source: {$source->name}");

            try {
                $articles = $this->fetchArticles($source->name);
                $articleCount = count($articles);
                $totalArticles += $articleCount;
                $successCount++;

                $this->info("{$source->name}: Fetched {$articleCount} articles");

            } catch (\Exception $e) {
                $failureCount++;
                $this->error("{$source->name}: Failed - " . $e->getMessage());
                Log::error("Source {$source->name} failed", ['exception' => $e]);
            }
        }

        $this->line('');
        $this->info('Summary:');
        $this->info("   Total sources processed: {$sourceCount}");
        $this->info("   Successful: {$successCount}");
        $this->info("   Failed: {$failureCount}");
        $this->info("   Total articles fetched: {$totalArticles}");

        return true;
    }

    private function fetchArticles(string $sourceName): array
    {
        $sourceName = strtolower($sourceName);
        $this->line("   Creating fetcher strategy for {$sourceName}...");

        Log::info("Fetching articles with {$sourceName} strategy");
        $fetcherStrategy = FetcherFactory::create($sourceName);

        $this->line("   Fetching articles from API...");
        $fetchStart = microtime(true);
        $articles = $fetcherStrategy->fetchArticles();
        $fetchTime = round(microtime(true) - $fetchStart, 2);

        $total = count($articles);
        $this->line("   Fetch completed in {$fetchTime}s - got {$total} articles");

        Log::debug("We got {$total} articles from {$sourceName}");

        if ($total > 0) {
            $this->line("   Persisting articles to database...");
            $persistStart = microtime(true);
            $this->articleRepository->persist($articles);
            $persistTime = round(microtime(true) - $persistStart, 2);
            $this->line("   Persistence completed in {$persistTime}s");
        } else {
            $this->line("   No articles to persist");
        }

        return $articles;
    }
}
