<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Resources\V1\Admin\User\UserCollection;
use App\Http\Resources\V1\Admin\User\UserResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type;
        if ($type && $type !== UserType::Admin->value) {
            $users = User::whereType(UserType::fromName($type)->value)->paginate($request->per_page ?? 10);
        } else {
            $users = User::where('type', '!=', UserType::Admin->value)->paginate($request->per_page ?? 10);
        }

        return Helper::successResponse(null, [
            'list' => new UserCollection($users),
            'pagination' => new PaginationCollection($users),
        ]);
    }

    public function show(User $user)
    {
        return Helper::successResponse(null, new UserResource($user));
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([...$request->validated(), 'password' => Hash::make($request->password ?? $request->email)]);

        return Helper::successResponse('(رمز عبور ایمیل کاربر است) کاربر با موفقیت ایجاد شد', new UserResource($user));
    }

    public function update(User $user, UpdateUserRequest $request)
    {
        $user->update($request->validated());

        return Helper::successResponse('کاربر با موفقیت ویرایش شد.');
    }

    public function delete(User $user)
    {
        if ($user->id !== auth()->id()) {
            $user->delete();

            return Helper::successResponse('موفقیت آمیز');
        }

        return Helper::errorResponse('ناموفق');
    }

    public function block(User $user)
    {
        if ($user->id !== auth()->id()) {
            $user->status = UserStatus::Blocked;
            $user->save();

            return Helper::successResponse('موفقیت آمیز');
        }

        return Helper::errorResponse('ناموفق');
    }

    public function unblock(User $user)
    {
        if ($user->id !== auth()->id()) {
            $user->status = UserStatus::Inactive;
            $user->save();

            return Helper::successResponse('موفقیت آمیز');
        }

        return Helper::errorResponse('ناموفق');
    }

    public function statuses(): JsonResponse
    {
        return Helper::successResponse(null, UserStatus::keyValue());
    }
}
