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
        if (! Organ::query()->whereKey(6)->exists()) {
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

        $entriesToCreate = 1000;
        $created = 0;
        $lastDate = null;

        while ($created < $entriesToCreate) {
            $useSameDay = $lastDate !== null && random_int(1, 100) <= 35;
            $date = $useSameDay ? $lastDate : Carbon::today()->subDays(random_int(0, 365));

            $dailyCount = $useSameDay ? random_int(1, 10) : 1;

            for ($i = 0; $i < $dailyCount && $created < $entriesToCreate; $i++) {
                $type = fake()->randomElement([TimelineEntry::TYPE_INCOME, TimelineEntry::TYPE_EXPENSE]);
                $title = $type === TimelineEntry::TYPE_INCOME
                    ? fake()->randomElement($persianIncomeTitles)
                    : fake()->randomElement($persianExpenseTitles);

                TimelineEntry::factory()->create([
                    'organ_id' => 6,
                    'type' => $type,
                    'title' => $title,
                    'date' => $date->copy(),
                    'amount' => $type === TimelineEntry::TYPE_INCOME
                        ? fake()->numberBetween(50_000_000, 100_000_000_000)
                        : fake()->numberBetween(10_000_000, 50_000_000_000),
                ]);

                $created++;
            }

            $lastDate = $date;
        }
    }
}
