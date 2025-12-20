<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Events\LogoutEvent;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\V1\Admin\Permission\PermissionCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        try {
            // Validate the request first
            $validated = $request->validated();

            $user = User::where('email', $validated['email'])->first();

            if (! $user) {
                return Helper::errorResponse(__('auth.failed'), null, 403);
            }

            if (! Hash::check($validated['password'], $user->password) || ($user->status !== UserStatus::Inactive && $user->status !== UserStatus::Active)) {
                return Helper::errorResponse(__('auth.failed'), null, 403);
            }

            if ($user->type === UserType::Unknown) {
                $user->type = UserType::General;
            }
            if ($user->status === UserStatus::Inactive) {
                $user->status = UserStatus::Active;
            }
            $user->logged_at = now();
            $user->save();

            DB::table('user_sessions')->insert([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => 'ورود',
                'type' => 1,
                'last_activity' => now(),
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return Helper::successResponse(__('auth.success_login'), [
                'user' => $user,
                'permissions' => new PermissionCollection($user->permissions()),
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
            ]);

            return Helper::errorResponse(__('auth.error_login'), 401);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        try {
            $user = $request->user();

            event(new LogoutEvent($user));

            $request->user()->currentAccessToken()->delete();

            DB::table('user_sessions')->insert([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => 'خروج',
                'type' => 2,
                'last_activity' => now(),
            ]);

            return response()->json([
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error during logout',
            ], 500);
        }
    }

    /**
     * Handle a password change request.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $user->update([
                'password' => Hash::make($request->string('password')),
            ]);

            return Helper::successResponse(__('auth.password_changed'));
        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return Helper::errorResponse(__('auth.password_change_failed'), null, 500);
        }
    }
}
