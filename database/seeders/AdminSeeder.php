<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'ramineghbaliankhob@gmail.com'],
            [
                'first_name' => 'رامین',
                'last_name' => 'اقبالیان',
                'password' => Hash::make('12345678'),
                'type' => User::TYPE_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ]
        );
    }
}
