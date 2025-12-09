<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::whereType(User::TYPE_ADMIN)->first();
        $superAdminRole = Role::whereSlug('super-admin')->first();
        $superAdminRole->permissions()->attach(Permission::whereUserType(User::TYPE_ADMIN)->get()->pluck('id')->toArray(), ['updated_by' => $user->id]);
        $user->roles()->attach($superAdminRole->id, ['assigned_by' => $user->id]);
    }
}
