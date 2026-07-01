<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sets_locale_from_x_locale_header(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password',
        ], ['X-Locale' => 'en']);

        $response->assertStatus(422);
        $response->assertJson(['message' => __('auth.credentials')]);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_defaults_to_app_locale_without_header(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $this->assertSame('es', app()->getLocale());
    }
}
