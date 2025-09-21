# Setting Service Documentation

## Overview

The Setting Service provides a comprehensive solution for managing application settings in your Laravel application. It includes caching, validation, type casting, and a full REST API for managing settings.

## Features

- ✅ **CRUD Operations**: Create, read, update, and delete settings
- ✅ **Caching**: Built-in caching for improved performance
- ✅ **Type Casting**: Automatic serialization/deserialization of complex data types
- ✅ **Validation**: Key format validation and input validation
- ✅ **Bulk Operations**: Set and get multiple settings at once
- ✅ **Prefix Support**: Get/delete settings by prefix
- ✅ **REST API**: Complete API endpoints for frontend integration
- ✅ **Helper Functions**: Easy-to-use helper functions and facades
- ✅ **Testing**: Comprehensive unit and feature tests

## Installation

The setting service is already integrated into your application. The following components are included:

1. **SettingService** - Core service class
2. **SettingServiceProvider** - Service provider for dependency injection
3. **Setting Facade** - Facade for easy access
4. **SettingController** - REST API controller
5. **Helper Functions** - Global helper functions

## Usage

### 1. Using the Service Class

```php
use App\Services\SettingService;

$settingService = app(SettingService::class);

// Set a setting
$settingService->set('app.name', 'My Application');

// Get a setting
$appName = $settingService->get('app.name', 'Default Name');

// Set multiple settings
$settingService->setMultiple([
    'app.name' => 'My Application',
    'app.version' => '1.0.0',
    'app.debug' => true
]);

// Get multiple settings
$settings = $settingService->getMultiple(['app.name', 'app.version']);

// Check if setting exists
if ($settingService->has('app.name')) {
    // Setting exists
}

// Delete a setting
$settingService->delete('app.name');

// Get all settings
$allSettings = $settingService->all();
```

### 2. Using the Facade

```php
use App\Facades\Setting;

// Set a setting
Setting::set('app.name', 'My Application');

// Get a setting
$appName = Setting::get('app.name', 'Default Name');

// Set multiple settings
Setting::setMultiple([
    'app.name' => 'My Application',
    'app.version' => '1.0.0'
]);

// Get multiple settings
$settings = Setting::getMultiple(['app.name', 'app.version']);

// Check if setting exists
if (Setting::has('app.name')) {
    // Setting exists
}

// Delete a setting
Setting::delete('app.name');

// Get all settings
$allSettings = Setting::all();
```

### 3. Using Helper Functions

```php
use App\Helpers\Helper;

// Get a setting (simplest way)
$appName = Helper::setting('app.name', 'Default Name');
```

### 4. Using Dependency Injection

```php
class MyController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    public function index()
    {
        $appName = $this->settingService->get('app.name');
        return view('dashboard', compact('appName'));
    }
}
```

## Data Types

The setting service automatically handles different data types:

```php
// String
Setting::set('app.name', 'My Application');

// Integer
Setting::set('app.max_users', 100);

// Boolean
Setting::set('app.debug', true);

// Array
Setting::set('app.config', [
    'database' => 'mysql',
    'cache' => 'redis'
]);

// Object (will be serialized as JSON)
Setting::set('app.settings', (object)['theme' => 'dark']);
```

## Prefix Operations

You can work with settings that share a common prefix:

```php
// Set settings with prefix
Setting::set('app.name', 'My Application');
Setting::set('app.version', '1.0.0');
Setting::set('app.debug', true);

// Get all settings with 'app.' prefix
$appSettings = Setting::getByPrefix('app.');
// Returns: ['app.name' => 'My Application', 'app.version' => '1.0.0', 'app.debug' => true]

// Delete all settings with 'app.' prefix
$deletedCount = Setting::deleteByPrefix('app.');
// Returns: 3 (number of deleted settings)
```

## Caching

The service includes built-in caching for improved performance:

```php
// Cache is automatically managed
Setting::set('app.name', 'My Application'); // Cache is cleared
$name = Setting::get('app.name'); // Value is cached for 1 hour

// Manually clear cache
Setting::clearAllCache();
```

## API Endpoints

The service provides a complete REST API:

### Authentication Required
All endpoints require authentication via Sanctum.

### Get All Settings
```http
GET /api/settings
```

### Get Specific Setting
```http
GET /api/settings/get?key=app.name
```

### Set Setting
```http
POST /api/settings/set
Content-Type: application/json

{
    "key": "app.name",
    "value": "My Application"
}
```

### Set Multiple Settings
```http
POST /api/settings/set-multiple
Content-Type: application/json

{
    "settings": {
        "app.name": "My Application",
        "app.version": "1.0.0",
        "app.debug": true
    }
}
```

### Get Multiple Settings
```http
POST /api/settings/get-multiple
Content-Type: application/json

{
    "keys": ["app.name", "app.version"]
}
```

### Check if Setting Exists
```http
GET /api/settings/has?key=app.name
```

### Delete Setting
```http
DELETE /api/settings/delete?key=app.name
```

### Get Settings by Prefix
```http
GET /api/settings/by-prefix?prefix=app.
```

### Delete Settings by Prefix
```http
DELETE /api/settings/by-prefix?prefix=app.
```

### Clear Cache
```http
POST /api/settings/clear-cache
```

## Validation

### Key Format Validation
Setting keys must follow these rules:
- Only alphanumeric characters, dots, underscores, and hyphens
- Maximum 255 characters
- Examples: `app.name`, `database.host`, `user-settings.theme`

### Value Validation
- Values are required when setting
- Any data type is allowed (will be serialized automatically)

## Error Handling

The service provides comprehensive error handling:

```php
try {
    Setting::set('invalid key!', 'value');
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->errors();
}
```

## Testing

Run the tests to ensure everything works correctly:

```bash
# Run unit tests
php artisan test tests/Unit/SettingServiceTest.php

# Run feature tests
php artisan test tests/Feature/SettingControllerTest.php

# Run all tests
php artisan test
```

## Best Practices

1. **Use Descriptive Keys**: Use dot notation for hierarchical settings
   ```php
   Setting::set('app.database.host', 'localhost');
   Setting::set('app.database.port', 3306);
   ```

2. **Group Related Settings**: Use prefixes for related settings
   ```php
   Setting::set('email.smtp.host', 'smtp.gmail.com');
   Setting::set('email.smtp.port', 587);
   Setting::set('email.from', 'noreply@example.com');
   ```

3. **Provide Default Values**: Always provide default values when getting settings
   ```php
   $timeout = Setting::get('app.timeout', 30);
   ```

4. **Use Caching Wisely**: The service automatically caches settings for 1 hour
   ```php
   // Cache is automatically managed
   Setting::set('app.name', 'New Name'); // Cache is cleared
   ```

5. **Handle Errors Gracefully**: Always handle potential validation errors
   ```php
   try {
       Setting::set($key, $value);
   } catch (ValidationException $e) {
       // Handle error
   }
   ```

## Configuration

You can customize the service behavior by modifying the `SettingService` class:

```php
class SettingService
{
    protected string $cachePrefix = 'setting_';
    protected int $cacheTtl = 3600; // 1 hour in seconds
}
```

## Database Schema

The settings table structure:

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(255) NOT NULL,
    value LONGTEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## Performance Considerations

- Settings are cached for 1 hour by default
- Use `getMultiple()` for fetching multiple settings
- Use `setMultiple()` for setting multiple values
- Cache is automatically cleared when settings are updated
- Consider using Redis for better cache performance in production
