<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bank>
 */
class BankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $enName = fake()->company();

        return [
            'name' => fake()->company(),
            'en_name' => $enName,
            'logo' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
