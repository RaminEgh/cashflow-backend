<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Common\ProfileResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /**
     * Get the authenticated user's profile with roles and permissions.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $this->userService->getProfile($request->user());

        return Helper::successResponse('', new ProfileResource($user));
    }
}
