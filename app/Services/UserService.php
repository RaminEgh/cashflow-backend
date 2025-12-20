<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * Get user profile with roles and permissions.
     */
    public function getProfile(User $user): User
    {
        return $user->load('roles.permissions');
    }
}
