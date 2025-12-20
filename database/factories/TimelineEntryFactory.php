<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimelineEntry>
 */
class TimelineEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organ_id' => \App\Models\Organ::factory(),
            'type' => $this->faker->randomElement([\App\Models\TimelineEntry::TYPE_INCOME, \App\Models\TimelineEntry::TYPE_EXPENSE]),
            'title' => $this->faker->sentence(3),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'amount' => $this->faker->randomFloat(2, 1000000, 100000000),
        ];
    }

    /**
     * Create an income timeline entry
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \App\Models\TimelineEntry::TYPE_INCOME,
            'title' => 'Sales to customer '.$this->faker->name(),
        ]);
    }

    /**
     * Create an expense timeline entry
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \App\Models\TimelineEntry::TYPE_EXPENSE,
            'title' => 'Purchase of raw materials',
        ]);
    }
}
