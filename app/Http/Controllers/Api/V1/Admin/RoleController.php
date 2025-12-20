<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\CacheKey;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Http\Resources\V1\Admin\Role\RoleCollection;
use App\Http\Resources\V1\Admin\Role\RoleResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = Role::paginate($request->per_page ?? 10);

        return Helper::successResponse(null, [
            'list' => new RoleCollection($roles),
            'pagination' => new PaginationCollection($roles),
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $role = Role::create([
                'slug' => $request->slug,
                'label' => $request->label,
                'user_type' => \App\Enums\UserType::Admin->value,
                'description' => $request->description,
                'created_by' => auth()->user()->id,
                'updated_by' => auth()->user()->id,
            ]);

            $role->permissions()->syncWithPivotValues($request->permissions, ['updated_by' => auth()->user()->id]);

            return Helper::successResponse('سمت جدید با موفقیت ایجاد شد.');
        } catch (Exception $exception) {
            return Helper::errorResponse('در ایجاد سمت جدید خطایی رخ داد.', $exception->getMessage());
        }
    }

    public function show(Role $role): JsonResponse
    {
        return Helper::successResponse(null, new RoleResource($role));
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role->permissions()->syncWithPivotValues($request->permissions, ['updated_at' => now()]);
        $permissions = Permission::WhereIn('id', $request->permissions);
        foreach ($role->users()->get() as $user) {
            foreach ($permissions->get() as $permission) {
                Cache::forget(CacheKey::ROLES_PERMISSION.$permission->slug.'_'.$user->id);
            }
        }

        return Helper::successResponse('سمت با موفقیت ویرایش شد.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return Helper::successResponse('موفقیت آمیز');
    }
}
