<?php

namespace Database\Seeders;

use App\Constants\AdminPermissionKey;
use App\Constants\OrganPermissionKey;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parentId = 0;

        foreach (AdminPermissionKey::PERMISSIONS as $key => $permission) {

            if (array_key_exists($key, AdminPermissionKey::PARENT_PERMISSIONS)) {
                $parentId = 0;
            }

            $permissionData = [
                'slug' => $key,
                'label' => $permission,
                'parent_id' => $parentId,
                'user_type' => User::TYPE_ADMIN,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $permissionObj = Permission::create($permissionData);

            if (array_key_exists($key, AdminPermissionKey::PARENT_PERMISSIONS)) {
                $parentId = $permissionObj->id;
            }
        }

        $parentId = 0;

        foreach (OrganPermissionKey::PERMISSIONS as $key => $permission) {

            if (array_key_exists($key, OrganPermissionKey::PARENT_PERMISSIONS)) {
                $parentId = 0;
            }

            $permissionData = [
                'slug' => $key,
                'label' => $permission,
                'parent_id' => $parentId,
                'user_type' => User::TYPE_ORGAN,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $permissionObj = Permission::create($permissionData);

            if (array_key_exists($key, OrganPermissionKey::PARENT_PERMISSIONS)) {
                $parentId = $permissionObj->id;
            }
        }

    }
}
