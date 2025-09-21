<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get the value attribute with automatic type casting
     *
     * @param string $value
     * @return mixed
     */
    public function getValueAttribute($value)
    {
        // Try to decode as JSON first
        $decoded = json_decode($value, true);
        
        // If JSON decode was successful and didn't return null, use decoded value
        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
            return $decoded;
        }
        
        // Otherwise return as string
        return $value;
    }

    /**
     * Set the value attribute with automatic serialization
     *
     * @param mixed $value
     * @return void
     */
    public function setValueAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['value'] = $value;
        } else {
            $this->attributes['value'] = json_encode($value);
        }
    }

    /**
     * Scope to get settings by prefix
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $prefix
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPrefix($query, string $prefix)
    {
        return $query->where('key', 'like', $prefix . '%');
    }

    /**
     * Find setting by key
     *
     * @param string $key
     * @return Setting|null
     */
    public static function findByKey(string $key): ?Setting
    {
        return static::where('key', $key)->first();
    }

    /**
     * Get setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::findByKey($key);
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function setValue(string $key, $value): bool
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        ) !== null;
    }
}
