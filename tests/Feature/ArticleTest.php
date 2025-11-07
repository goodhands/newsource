<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_article(): void
    {
        $response = $this->getJson('/api/articles');

        $response
            ->assertStatus(200)
            ->assertJson([
                'articles' => [
                    // Factory here
                ],
                'page' => [
                    'total' => 1,
                    'limit' => 10,
                    'offset' => 0,
                    'total_count' => 1,
                ]
            ]);
    }

    public function test_article_with_filters(): void
    {
        $response = $this->getJson('/api/articles', [
            'filters' => [
                'published_at' => now()->format('Y-m-d'),
                'category' => 'Nuclear',
                'source' => 'bbc'
            ]
        ]);

        $response
                ->assertStatus(200)
                ->assertJson([
                    'articles' => [
                        // Factory here
                    ],
                    'page' => [
                        'total' => 1,
                        'limit' => 10,
                        'offset' => 0,
                        'total_count' => 1,
                    ]
                ]);
    }

    public function test_article_with_search(): void
    {
        $response = $this->getJson('/api/articles', [
            'q' => 'Russia Ukraine war'
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'articles' => [
                    // Factory here
                ],
                'page' => [
                    'total' => 1,
                    'limit' => 10,
                    'offset' => 0,
                    'total_count' => 1,
                ]
            ]);
    }

    public function test_article_with_user_preferences(): void
    {
//        TODO: It may be worth using regular sorting for this. It doesn't look like the
//        API should be aware of user_preferences.
        $response = $this->getJson('/api/articles', [
            'user_preferences' => [
                'categories' => [1, 2, 3],
                'sources' => [11, 5, 3],
                'authors' => [2, 5, 9]
            ]
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'articles' => [
                    // Factory here
                ],
                'page' => [
                    'total' => 1,
                    'limit' => 10,
                    'offset' => 0,
                    'total_count' => 1,
                ]
            ]);
    }
}
