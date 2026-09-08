<?php

namespace Tests\Concerns;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait CreatesUsers
{
    protected function admin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function user(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Authenticate as the given user with a real Sanctum personal access
     * token so currentAccessToken() behaves as it does outside of tests.
     */
    protected function actingAsAdmin(array $attributes = []): User
    {
        $admin = $this->admin($attributes);
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    protected function actingAsUser(array $attributes = []): User
    {
        $user = $this->user($attributes);
        Sanctum::actingAs($user, ['*']);

        return $user;
    }
}
