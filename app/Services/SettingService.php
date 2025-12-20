<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SettingService
{
    protected string $cachePrefix = 'setting_';

    protected int $cacheTtl = 3600; // 1 hour

    /**
     * Get a setting value by key
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->cachePrefix.$key;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return $this->castValue($setting->value);
        });
    }

    /**
     * Set a setting value
     *
     * @param  mixed  $value
     *
     * @throws ValidationException
     */
    public function set(string $key, $value): bool
    {
        $this->validateKey($key);

        $serializedValue = $this->serializeValue($value);

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $serializedValue]
        );

        if ($setting) {
            $this->clearCache($key);

            return true;
        }

        return false;
    }

    /**
     * Get multiple settings at once
     */
    public function getMultiple(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Set multiple settings at once
     *
     * @throws ValidationException
     */
    public function setMultiple(array $settings): bool
    {
        $this->validateKeys(array_keys($settings));

        $success = true;

        foreach ($settings as $key => $value) {
            if (! $this->set($key, $value)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if a setting exists
     */
    public function has(string $key): bool
    {
        return Setting::where('key', $key)->exists();
    }

    /**
     * Delete a setting
     */
    public function delete(string $key): bool
    {
        $deleted = Setting::where('key', $key)->delete();

        if ($deleted) {
            $this->clearCache($key);

            return true;
        }

        return false;
    }

    /**
     * Get all settings
     */
    public function all(): array
    {
        return Cache::remember('all_settings', $this->cacheTtl, function () {
            $settings = Setting::all();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $this->castValue($setting->value);
            }

            return $result;
        });
    }

    /**
     * Clear all settings cache
     */
    public function clearAllCache(): void
    {
        Cache::forget('all_settings');

        // Clear individual setting caches
        $settings = Setting::pluck('key');
        foreach ($settings as $key) {
            $this->clearCache($key);
        }
    }

    /**
     * Clear cache for a specific setting
     */
    protected function clearCache(string $key): void
    {
        Cache::forget($this->cachePrefix.$key);
    }

    /**
     * Validate setting key
     *
     * @throws ValidationException
     */
    protected function validateKey(string $key): void
    {
        $validator = Validator::make(['key' => $key], [
            'key' => 'required|string|max:255|regex:/^[a-zA-Z0-9._-]+$/',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate multiple setting keys
     *
     * @throws ValidationException
     */
    protected function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    /**
     * Serialize value for storage
     *
     * @param  mixed  $value
     */
    protected function serializeValue($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value);
    }

    /**
     * Cast value from storage
     *
     * @return mixed
     */
    protected function castValue(string $value)
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
     * Get settings with a specific prefix
     */
    public function getByPrefix(string $prefix): array
    {
        $cacheKey = 'settings_prefix_'.$prefix;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($prefix) {
            $settings = Setting::where('key', 'like', $prefix.'%')->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $this->castValue($setting->value);
            }

            return $result;
        });
    }

    /**
     * Delete settings with a specific prefix
     *
     * @return int Number of deleted settings
     */
    public function deleteByPrefix(string $prefix): int
    {
        $settings = Setting::where('key', 'like', $prefix.'%')->get();
        $count = $settings->count();

        Setting::where('key', 'like', $prefix.'%')->delete();

        // Clear cache for deleted settings
        foreach ($settings as $setting) {
            $this->clearCache($setting->key);
        }

        Cache::forget('settings_prefix_'.$prefix);
        $this->clearAllCache();

        return $count;
    }
}
