<?php

namespace App\Models;

use App\Constants\CacheKey;
use App\Enums\UserStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Backward compatibility constants - use UserType and UserStatus enums instead
    public const TYPE_UNKNOWN = 0;

    public const TYPE_ADMIN = 1;

    public const TYPE_ORGAN = 2;

    public const TYPE_GENERAL = 3;

    public const STATUS_INACTIVE = 0;

    public const STATUS_ACTIVE = 1;

    public const STATUS_BLOCKED = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id', 'created_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['name'];

    protected function getNameAttribute(): string
    {
        return ($this->attributes['first_name'] ?? '') . ' ' . ($this->attributes['last_name'] ?? '');
    }

    public function getTypeName(): string
    {
        $type = $this->type instanceof UserType ? $this->type : UserType::from($this->type ?? 0);

        return $type->name();
    }

    public function getStatusName(): string
    {
        $status = $this->status instanceof UserStatus ? $this->status : UserStatus::from($this->status ?? 0);

        return $status->name();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'logged_at' => 'datetime',
            'password' => 'hashed',
            'type' => UserType::class,
            'status' => UserStatus::class,
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function organs(): BelongsToMany
    {
        return $this->belongsToMany(Organ::class, 'organ_admin', 'admin_id', 'organ_id')->withTimestamps();
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            Cache::forget(CacheKey::USER_ROLE . $role . '_' . auth()->id());

            return Cache::remember(CacheKey::USER_ROLE . $role . '_' . auth()->id(), CacheKey::TIME_TEN_MINUTES, function () use ($role) {
                return $this->roles->contains('slug', $role);
            });
        }

        foreach ($role as $r) {
            if ($this->hasRole($r->slug)) {
                return true;
            }
        }

        return false;
    }

    public function permissions()
    {
        return Permission::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles->pluck('id'));
        })->get();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return Cache::remember("user_permission_{$this->id}_{$permissionSlug}", now()->addMinutes(10), function () use ($permissionSlug) {
            return $this->roles()
                ->whereHas('permissions', function ($query) use ($permissionSlug) {
                    $query->where('slug', $permissionSlug);
                })->exists();
        });
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }
}
