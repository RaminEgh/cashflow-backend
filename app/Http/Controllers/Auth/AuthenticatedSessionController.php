<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\V1\Admin\Permission\PermissionCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\LogoutEvent;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {

        try {
            $user = User::where('email', $request->email)->first();

            if (!Hash::check($request->password, $user->password) || ($user->status !== User::STATUS_INACTIVE && $user->status !== User::STATUS_ACTIVE)) {
                return Helper::errorResponse(__('auth.failed'), null, 403);
            }

            if ($user->type === User::TYPE_UNKNOWN) {
                $user->type = User::TYPE_GENERAL;
            }
            if ($user->status === User::STATUS_INACTIVE) {
                $user->status = User::STATUS_ACTIVE;
            }
            $user->logged_at = now();
            $user->save();

            DB::table('user_sessions')->insert([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => 'ورود',
                'type' => 1,
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return Helper::successResponse(__('auth.success_login'), [
                'user' => $user,
                'permissions' => new PermissionCollection($user->permissions()),
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            \Log::error('Login failed', [
                'error' => $e->getMessage()
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
            ]);

            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            \Log::error('Logout failed', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Error during logout'
            ], 500);
        }
    }
}
