<?php

namespace App\Console\Commands;

use App\Domain\Articles\Services\ArticleAggregatorService;
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
        protected ArticleAggregatorService $articleAggregatorService
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $this->withProgressBar(10, function () {
//            $this->articleAggregatorService->aggregateLatestArticles();
//        })
        Log::info("Fetching articles");
        $this->articleAggregatorService->aggregateLatestArticles();
    }
}
