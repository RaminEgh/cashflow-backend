<?php

namespace Database\Seeders;

use App\Models\Organ;
use App\Models\TimelineEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimelineEntrySeeder extends Seeder
{
    public function run(): void
    {
        $organs = Organ::all();

        if ($organs->isEmpty()) {
            $this->command->warn('No organs found. Please seed organs first.');

            return;
        }

        $persianIncomeTitles = [
            'فروش روزانه',
            'دریافت وجه',
            'وصول فاکتور',
            'سود بانکی',
            'درآمد خدمات',
            'پروژه تکمیل‌شده',
        ];

        $persianExpenseTitles = [
            'خرید مواد اولیه',
            'پرداخت حقوق',
            'هزینه حمل و نقل',
            'پرداخت مالیات',
            'هزینه اجاره',
            'هزینه تعمیرات',
        ];

        $entriesPerOrgan = 1000;
        $totalCreated = 0;

        foreach ($organs as $organ) {
            $this->command->line("Processing organ ID: {$organ->id} ({$organ->name})");

            $created = 0;
            $lastDate = null;

            while ($created < $entriesPerOrgan) {
                $useSameDay = $lastDate !== null && random_int(1, 100) <= 35;
                $date = $useSameDay ? $lastDate : Carbon::today()->subDays(random_int(0, 365));

                $dailyCount = $useSameDay ? random_int(1, 10) : 1;

                for ($i = 0; $i < $dailyCount && $created < $entriesPerOrgan; $i++) {
                    $type = fake()->randomElement([TimelineEntry::TYPE_INCOME, TimelineEntry::TYPE_EXPENSE]);
                    $title = $type === TimelineEntry::TYPE_INCOME
                        ? fake()->randomElement($persianIncomeTitles)
                        : fake()->randomElement($persianExpenseTitles);

                    TimelineEntry::factory()->create([
                        'organ_id' => $organ->id,
                        'type' => $type,
                        'title' => $title,
                        'date' => $date->format('Y-m-d'),
                        'amount' => $type === TimelineEntry::TYPE_INCOME
                            ? fake()->numberBetween(50_000_000, 100_000_000_000)
                            : fake()->numberBetween(10_000_000, 50_000_000_000),
                    ]);

                    $created++;
                    $totalCreated++;
                }

                $lastDate = $date;
            }

            $this->command->line("  Created {$created} timeline entries for organ ID: {$organ->id}");
        }

        $this->command->info("Total timeline entries created: {$totalCreated}");
    }
}
