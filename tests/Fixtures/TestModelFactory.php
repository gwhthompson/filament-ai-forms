<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestModel>
 */
class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
        ];
    }
}
