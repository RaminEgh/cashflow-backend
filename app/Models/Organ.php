<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Organ extends Model
{
    /** @use HasFactory<\Database\Factories\OrganFactory> */
    use HasFactory, HasSlug;

    protected $guarded = ['id'];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organ_admin', 'organ_id', 'admin_id')->withTimestamps();
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(TimelineEntry::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('en_name')
            ->saveSlugsTo('slug');
    }
}
