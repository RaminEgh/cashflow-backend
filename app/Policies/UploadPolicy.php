<?php

namespace App\Policies;

use App\Models\Upload;
use App\Models\User;

class UploadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Upload $upload): bool
    {
        if ($upload->is_private === Upload::PUBLIC_UPLOAD) {
            return true;
        }

        return $upload->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Upload $upload): bool
    {
        return $upload->user_id === $user->id;
    }

    public function delete(User $user, Upload $upload): bool
    {
        return $upload->user_id === $user->id;
    }

    public function restore(User $user, Upload $upload): bool
    {
        return $upload->user_id === $user->id;
    }

    public function forceDelete(User $user, Upload $upload): bool
    {
        return $upload->user_id === $user->id;
    }
}
