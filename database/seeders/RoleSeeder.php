<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::whereType(User::TYPE_ADMIN)->first();
        Role::create([
            'slug' => 'super-admin',
            'label' => 'مدیرکل برنامه',
            'user_type' => User::TYPE_ADMIN,
            'description' => 'این نقش به صورت خودکار از طریق RoleSeeder.php توسط برنامه ساخته شده است.',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        Role::create([
            'slug' => 'super-organ-admin',
            'label' => 'مدیر کل سازمان',
            'user_type' => User::TYPE_ORGAN,
            'description' => 'این نقش به صورت خودکار از طریق RoleSeeder.php توسط برنامه ساخته شده است.',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);
    }
}
