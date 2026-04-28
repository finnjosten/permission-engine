<?php

namespace Finn\PermissionEngine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Finn\PermissionEngine\Models\Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = \Finn\PermissionEngine\Models\Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'last_seen_at' => now(),
        ];
    }
}