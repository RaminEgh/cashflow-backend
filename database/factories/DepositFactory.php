<?php

namespace Database\Factories;

use App\Enums\DepositType;
use App\Models\Bank;
use App\Models\Organ;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deposit>
 */
class DepositFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organ_id' => Organ::factory(),
            'bank_id' => Bank::factory(),
            'branch_code' => fake()->numberBetween(100, 999),
            'branch_name' => fake()->company(),
            'number' => fake()->numerify('##########'),
            'sheba' => fake()->numerify('IR##########################'),
            'type' => fake()->randomElement(DepositType::cases()),
            'currency' => 'IR-Rial',
            'description' => fake()->sentence(),
            'balance' => fake()->numberBetween(0, 1000000000),
            'rahkaran_balance' => fake()->numberBetween(0, 1000000000),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
