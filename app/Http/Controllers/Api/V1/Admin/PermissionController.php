<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Permission\StorePermissionRequest;
use App\Http\Requests\Admin\Permission\UpdatePermissionRequest;
use App\Http\Resources\V1\Admin\Permission\PermissionCollection;
use App\Http\Resources\V1\Admin\Permission\PermissionResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::paginate();
        return Helper::successResponse(null, [
            'list' => new PermissionCollection($permissions),
            'pagination' => new PaginationCollection($permissions)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): JsonResponse
    {
        return Helper::successResponse(null, new PermissionResource($permission));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission->update(['description' => $request->description]);
        return Helper::successResponse(__('crud.d_edited', ['source' => __('sources.permission'), 'name' => $permission->label], [
            'permission' => new PermissionResource($permission)
        ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        //
    }
}
