<?php

namespace Tests\Feature;

use App\Domain\Sources\Fetchers\Strategies\NyTimesStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Sources\Fetchers\FetcherFactory;
use App\Domain\Sources\Fetchers\Strategies\GuardianStrategy;
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

        $this->assertCount(10, $articles);
        $this->assertEquals('Slow Cooker Red Lentil Pumpkin Soup', $articles[0]['title']);
    }

    public function test_theguardian_fetcher(): void
    {
        $headers = [
            "Date" => "Sat, 08 Nov 2025 19:08:30 GMT",
            "Content-Type" => "application/json",
            "Content-Length" => "53779",
            "Connection" => "keep-alive",
            "Set-Cookie" => [
                "AWSALB=Kytsr5RyD/XVgDBzXOPOqSLo5GsfXcvr7lKg0Lq4wNT9eqtJHvY2r9paHwxe7/IJzUTzJLYU57cHLF/kVJch13Rw0xPmB9aINtBqfQumDhRsZ3zDpT6r796S4QSJ; Expires=Sat, 15 Nov 2025 19:08:30 GMT; Path=/",
                "AWSALBCORS=Kytsr5RyD/XVgDBzXOPOqSLo5GsfXcvr7lKg0Lq4wNT9eqtJHvY2r9paHwxe7/IJzUTzJLYU57cHLF/kVJch13Rw0xPmB9aINtBqfQumDhRsZ3zDpT6r796S4QSJ; Expires=Sat, 15 Nov 2025 19:08:30 GMT; Path=/; SameSite=None; Secure"
            ],
            "X-RateLimit-Remaining-Day" => "499",
            "X-RateLimit-Limit-Day" => "500",
            "RateLimit-Reset" => "30",
            "RateLimit-Remaining" => "59",
            "RateLimit-Limit" => "60",
            "X-RateLimit-Remaining-Minute" => "59",
            "X-RateLimit-Limit-Minute" => "60",
            "Access-Control-Allow-Credentials" => "true",
            "Access-Control-Allow-Origin" => "*",
            "Cache-Control" => "max-age=0",
            "Content-Encoding" => "gzip",
            "Server" => "Concierge"
        ];

        $responseStub = file_get_contents(__DIR__ . '/fixtures/theguardian.json');
        $responseStub = json_decode($responseStub, true, 512, JSON_THROW_ON_ERROR);

        Http::fake([
            'guardianapis.com/*' => Http::response($responseStub, 200, $headers),
        ]);

        $strategy = FetcherFactory::create('guardian');
        $articles = $strategy->fetchArticles();

        Http::assertSent(static function ($request) {
            return str_contains($request->url(), 'https://content.guardianapis.com/search') &&
                $request->method() === 'GET';
        });

        $this->assertCount(10, $articles);
        $this->assertEquals('Passengers start to feel bite of flight cuts amid US government shutdown', $articles[0]['title']);
    }
}
