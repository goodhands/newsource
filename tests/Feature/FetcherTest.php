<?php

namespace Tests\Feature;

use App\Domain\Sources\Fetchers\Strategies\NyTimesStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Sources\Fetchers\FetcherFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use JsonException;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class FetcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     * @throws ConnectionException
     * @throws JsonException
     */
    public function test_nytimes_fetcher(): void
    {
        $headers = [
            'Date' => 'Fri, 07 Nov 2025 16:13:09 GMT',
            'Content-Type' => 'application/json; charset=utf-8',
            'Transfer-Encoding' => 'chunked',
            'Connection' => 'keep-alive',
            'cache-control' => 'max-age=300',
            'Server' => 'Google Frontend',
            'Via' => '1.1 google, 1.1 varnish',
            'Content-Encoding' => 'gzip',
            'Accept-Ranges' => 'bytes',
            'Age' => '0',
            'X-Served-By' => 'cache-iad-kiad7000069-IAD',
            'X-Cache' => 'MISS',
            'X-Cache-Hits' => '0',
            'X-Timer' => 'S1762531989.584649,VS0,VE482',
            'Vary' => 'Accept-Encoding',
            'x-nyt-mktg-group' => 'group1',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Accept, Content-Type, Origin, X-Forwarded-For, X-Prototype-Version, X-Requested-With',
            'Access-Control-Expose-Headers' => 'Content-Length, X-JSON',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Strict-Transport-Security' => 'max-age=63072000; preload; includeSubdomains',
        ];

        $responseStub = file_get_contents(__DIR__ . '/fixtures/nytimes.json');
        $responseStub = json_decode($responseStub, true, 512, JSON_THROW_ON_ERROR);

        Http::fake([
            'nytimes.com/*' => Http::response($responseStub, 200, $headers)
        ]);

        $strategy = FetcherFactory::create('nytimes');
        $articles = $strategy->fetchArticles();

        Http::assertSent(static function ($request) {
            return str_contains($request->url(), NyTimesStrategy::BASE_URL) &&
                $request->method() === 'GET';
        });

        $this->assertCount(3, $articles);
        $this->assertCount(10, $articles['response']['docs']);
        $this->assertEquals('Sarah DiGregorio', $articles['response']['docs'][0]['byline']['original']);
    }
}
