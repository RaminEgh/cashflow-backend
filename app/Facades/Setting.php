<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, $default = null)
 * @method static bool set(string $key, $value)
 * @method static array getMultiple(array $keys)
 * @method static bool setMultiple(array $settings)
 * @method static bool has(string $key)
 * @method static bool delete(string $key)
 * @method static array all()
 * @method static void clearAllCache()
 * @method static array getByPrefix(string $prefix)
 * @method static int deleteByPrefix(string $prefix)
 */
class Setting extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'settings';
    }
}
