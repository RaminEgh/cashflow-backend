<?php

namespace App\Models;

use App\Constants\CacheKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    const TYPE_UNKNOWN = 0;
    const TYPE_ADMIN = 1;
    const TYPE_ORGAN = 2;
    const TYPE_GENERAL = 3;

    const TYPES = ['unknown' => self::TYPE_UNKNOWN, 'admin' => self::TYPE_ADMIN, 'organ' => self::TYPE_ORGAN, 'general' => self::TYPE_GENERAL,];


    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_BLOCKED = 2;

    const STATUSES_KEY_VALUE = [
        [
            "id" => self::STATUS_INACTIVE,
            "name" => "Inactive",
        ],
        [
            "id" => self::STATUS_ACTIVE,
            "name" => "Active",
        ],
        [
            "id" => self::STATUS_BLOCKED,
            "name" => "Blocked",
        ]
    ];

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
        switch ($this->type) {
            case self::TYPE_ADMIN:
                return 'admin';
            case self::TYPE_ORGAN:
                return 'organ';
            case self::TYPE_GENERAL:
                return 'general';
            default:
                return 'unknown';
        }
    }

    public function getStatusName(): string
    {
        switch ($this->status) {
            case self::STATUS_ACTIVE:
                return 'active';
            case self::STATUS_INACTIVE:
                return 'inactive';
            case self::STATUS_BLOCKED:
                return 'blocked';
            default:
                return 'unknown';
        }
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
            'password' => 'hashed',
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


    public function sessions(): HasMany {
        return $this->hasMany(UserSession::class);
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

}
