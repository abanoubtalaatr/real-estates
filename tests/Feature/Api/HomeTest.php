<?php

namespace Tests\Feature\Api;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_endpoint_returns_payload(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->getJson('/api/v1/home');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'best_selling',
                    'featured',
                    'recommended',
                ],
            ]);
    }
}
