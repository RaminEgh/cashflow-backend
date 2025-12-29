<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\CacheKey;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Access\StoreAccessRequest;
use App\Http\Requests\Admin\Admin\StoreAdminRequest;
use App\Http\Resources\V1\Admin\Admin\AdminCollection;
use App\Http\Resources\V1\Admin\Admin\AdminResource;
use App\Http\Resources\V1\Admin\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::whereType(UserType::Admin->value)->get();

        return Helper::successResponse(null, [
            'list' => new AdminCollection($users),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return Helper::successResponse(null, new AdminResource($user));
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        DB::beginTransaction();
        $user = User::create([...$request->validated(), 'password' => Hash::make($request->password), 'type' => UserType::Admin, 'status' => UserStatus::Active]);
        $user->roles()->syncWithPivotValues($request->roles, ['assigned_by' => auth()->user()->id]);
        DB::commit();

        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.admin'), 'name' => $user->name]), new UserResource($user));
    }

    public function update(StoreAccessRequest $request, User $user)
    {
        if ($user->type === UserType::Admin) {
            $rolesId = $request->roles;
            $user->roles()->syncWithPivotValues($rolesId, ['assigned_by' => auth()->user()->id]);
            foreach ($user->roles as $role) {
                Cache::forget(CacheKey::ROLE.$role->slug.'_'.$user->id);
            }

            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
            ]);

            return Helper::successResponse('نقش ها با موفقیت به کاربر داده شد');
        }

        return Helper::errorResponse('کاربر ادمین نیست', null, 422);
    }

    public function delete(User $user)
    {
        DB::beginTransaction();
        if ($user->type === UserType::Admin && $user->id !== auth()->id()) {
            $user->roles()->detach();
            Cache::forget(CacheKey::USER_ROLE.$user->id);
            $user->delete();
            DB::commit();

            return Helper::successResponse('کاربر با موفقیت حذف شد');
        } else {
            return Helper::errorResponse('نمی توانید ادمین خود را حذف کنید', null, 422);
        }
    }
}
