<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    private static int $userSequence = 1;

    protected function createUser(array $attributes = []): User
    {
        $sequence = self::$userSequence++;

        return User::query()->create(array_merge([
            'name' => 'Test User '.$sequence,
            'email' => 'test'.$sequence.'@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => null,
        ], $attributes));
    }
}
