<?php

namespace Finn\PermissionEngine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Finn\PermissionEngine\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = \Finn\PermissionEngine\Models\Role::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => fake()->userName(),
        ];
    }
}
