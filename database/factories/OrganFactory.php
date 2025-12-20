<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organ>
 */
class OrganFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'en_name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'description' => fake()->sentence(),
            'logo' => null,
            'background' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
