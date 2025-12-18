<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<TestUser> */
class TestUserFactory extends Factory
{
    protected $model = TestUser::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}
