<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Admin\StoreAdminRequest;
use App\Http\Resources\V1\Admin\User\UserCollection;
use App\Http\Resources\V1\Admin\User\UserResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::whereType(User::TYPE_ADMIN)->paginate();
        return Helper::successResponse(null, [
            'list' => new UserCollection($users),
            'pagination' => new PaginationCollection($users)
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return Helper::successResponse(null, new UserResource($user));
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $user = User::create([...$request->validated(), 'password' => Hash::make($request->password), 'type' => User::TYPE_ADMIN, 'status' => User::STATUS_ACTIVE]);

        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.admin'), 'name' => $user->name]), new UserResource($user));
    }

    public function update(User $user)
    {

    }

    public function delete(User $user)
    {

    }



}
