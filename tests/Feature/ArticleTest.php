<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthToken(): string
    {
        $user = User::factory()->create();

        $tokenResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tokens/create', [
                'token_name' => 'test-token'
            ]);

        return $tokenResponse->json('token');
    }

    /**
     * A basic feature test example.
     */
    public function test_article(): void
    {
        $token = $this->createAuthToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/articles');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }

    public function test_article_with_filters(): void
    {
        $token = $this->createAuthToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/articles', [
            'filters' => [
                'published_at' => now()->format('Y-m-d'),
                'category' => 'Nuclear',
                'source' => 'bbc'
            ]
        ]);

        $response
                ->assertStatus(200)
                ->assertJsonStructure([
                    'current_page',
                    'data',
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ]);
    }

    public function test_article_with_search(): void
    {
        $token = $this->createAuthToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/articles', [
            'q' => 'Russia Ukraine war'
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }

    public function test_article_with_user_preferences(): void
    {
        $token = $this->createAuthToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/articles', [
            'user_preferences' => [
                'categories' => [1, 2, 3],
                'sources' => [11, 5, 3],
                'authors' => [2, 5, 9]
            ]
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }
}
