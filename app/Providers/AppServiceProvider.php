<?php

namespace App\Providers;

use App\Constants\CacheKey;
use App\Models\Permission;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\QueryException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void                    
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (Schema::hasTable('cache')) {
                $allPermissions = Cache::remember(CacheKey::PERMISSIONS, CacheKey::TIME_TEN_MINUTES, function () {
                    return Permission::all();
                });

                foreach ($allPermissions as $permission) {
                    Gate::define($permission->slug, function ($user) use ($permission) {
                        $roles = Cache::remember(CacheKey::ROLES_PERMISSION . $permission->slug . '_' . auth()->id(), CacheKey::TIME_FIVE_MINUTES, function () use ($permission) {
                            return $permission->roles;

                        });

                        return $user->hasRole($roles);
                    });
                }
            }
        } catch (QueryException $e) {
            // Log the error but don't fail the application boot
            \Log::warning('Database not ready during boot: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Log any other errors but don't fail the application boot
            \Log::warning('Error during AppServiceProvider boot: ' . $e->getMessage());
        }
    }
}
