<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\CacheKey;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Access\StoreAccessRequest;
use App\Http\Resources\V1\Admin\Access\AccessCollection;
use App\Http\Resources\V1\Admin\User\UserResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AccessController extends Controller
{
    public function index(Request $request)
    {
        $users = User::whereType(User::TYPE_ADMIN)->paginate($request->per_page ?? 10);
        return Helper::successResponse(null, [
            'list' => new AccessCollection($users),
            'pagination' => new PaginationCollection($users)
        ]);
    }

    public function show(User $user)
    {
        return Helper::successResponse(null, new UserResource($user));

    }

    public function update(StoreAccessRequest $request, User $user)
    {
        if ($user->type === User::TYPE_ADMIN) {
            $rolesId = $request->roles;
            $user->roles()->syncWithPivotValues($rolesId, ['assigned_by' => auth()->user()->id]);
            foreach ($user->roles as $role) {
                Cache::forget(CacheKey::ROLE . $role->slug . '_' . $user->id);
            }

            return Helper::successResponse('نقش ها با موفقیت به کاربر داده شد');
        }

        return Helper::errorResponse('کاربر ادمین نیست', null, 422);
    }

}
