# Setting Service Usage Examples

## Basic Usage Examples

### 1. Simple String Settings

```php
use App\Facades\Setting;

// Set a simple string setting
Setting::set('app.name', 'My Cashflow Application');
Setting::set('app.description', 'A comprehensive cashflow management system');

// Get the setting with a default value
$appName = Setting::get('app.name', 'Default App Name');
$description = Setting::get('app.description', 'No description available');
```

### 2. Numeric Settings

```php
// Set numeric values
Setting::set('app.max_users', 100);
Setting::set('app.session_timeout', 3600);
Setting::set('app.currency_decimal_places', 2);

// Get numeric values
$maxUsers = Setting::get('app.max_users', 50);
$timeout = Setting::get('app.session_timeout', 1800);
$decimals = Setting::get('app.currency_decimal_places', 2);
```

### 3. Boolean Settings

```php
// Set boolean values
Setting::set('app.debug', true);
Setting::set('app.maintenance_mode', false);
Setting::set('app.auto_backup', true);

// Get boolean values
$isDebug = Setting::get('app.debug', false);
$isMaintenance = Setting::get('app.maintenance_mode', false);
$autoBackup = Setting::get('app.auto_backup', false);
```

### 4. Array Settings

```php
// Set array values
Setting::set('app.supported_currencies', ['USD', 'EUR', 'GBP', 'IRR']);
Setting::set('app.theme_colors', [
    'primary' => '#007bff',
    'secondary' => '#6c757d',
    'success' => '#28a745',
    'danger' => '#dc3545'
]);

// Get array values
$currencies = Setting::get('app.supported_currencies', ['USD']);
$colors = Setting::get('app.theme_colors', []);
```

### 5. Complex Object Settings

```php
// Set complex objects
Setting::set('app.database_config', [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'cashflow',
    'username' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci'
]);

Setting::set('app.email_config', [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'noreply@example.com',
        'password' => 'app_password'
    ],
    'from' => [
        'address' => 'noreply@example.com',
        'name' => 'Cashflow System'
    ]
]);

// Get complex objects
$dbConfig = Setting::get('app.database_config', []);
$emailConfig = Setting::get('app.email_config', []);
```

## Dashboard-Specific Examples

### 1. User Interface Settings

```php
// Dashboard layout settings
Setting::set('dashboard.sidebar_collapsed', false);
Setting::set('dashboard.default_page_size', 25);
Setting::set('dashboard.theme', 'light');
Setting::set('dashboard.language', 'en');

// Get UI settings
$sidebarCollapsed = Setting::get('dashboard.sidebar_collapsed', false);
$pageSize = Setting::get('dashboard.default_page_size', 25);
$theme = Setting::get('dashboard.theme', 'light');
$language = Setting::get('dashboard.language', 'en');
```

### 2. Financial Settings

```php
// Currency and financial settings
Setting::set('financial.default_currency', 'USD');
Setting::set('financial.currency_symbol', '$');
Setting::set('financial.decimal_places', 2);
Setting::set('financial.thousand_separator', ',');
Setting::set('financial.decimal_separator', '.');

// Tax settings
Setting::set('financial.tax_rate', 0.10);
Setting::set('financial.tax_inclusive', true);

// Get financial settings
$currency = Setting::get('financial.default_currency', 'USD');
$symbol = Setting::get('financial.currency_symbol', '$');
$taxRate = Setting::get('financial.tax_rate', 0.10);
```

### 3. Notification Settings

```php
// Email notification settings
Setting::set('notifications.email_enabled', true);
Setting::set('notifications.email_frequency', 'daily');
Setting::set('notifications.email_recipients', [
    'admin@example.com',
    'manager@example.com'
]);

// SMS notification settings
Setting::set('notifications.sms_enabled', false);
Setting::set('notifications.sms_provider', 'twilio');

// Get notification settings
$emailEnabled = Setting::get('notifications.email_enabled', true);
$emailFrequency = Setting::get('notifications.email_frequency', 'daily');
$smsEnabled = Setting::get('notifications.sms_enabled', false);
```

## Bulk Operations Examples

### 1. Set Multiple Settings at Once

```php
// Set multiple related settings
Setting::setMultiple([
    'app.name' => 'Cashflow Management System',
    'app.version' => '1.0.0',
    'app.environment' => 'production',
    'app.timezone' => 'UTC',
    'app.locale' => 'en'
]);

// Set dashboard settings
Setting::setMultiple([
    'dashboard.theme' => 'dark',
    'dashboard.sidebar_collapsed' => false,
    'dashboard.default_page_size' => 50,
    'dashboard.auto_refresh' => true,
    'dashboard.refresh_interval' => 30
]);
```

### 2. Get Multiple Settings at Once

```php
// Get multiple settings
$appSettings = Setting::getMultiple([
    'app.name',
    'app.version',
    'app.environment'
]);

// Get dashboard settings
$dashboardSettings = Setting::getMultiple([
    'dashboard.theme',
    'dashboard.sidebar_collapsed',
    'dashboard.default_page_size'
]);
```

## Prefix Operations Examples

### 1. Get Settings by Prefix

```php
// Get all app settings
$appSettings = Setting::getByPrefix('app.');
// Returns: ['app.name' => '...', 'app.version' => '...', 'app.environment' => '...']

// Get all dashboard settings
$dashboardSettings = Setting::getByPrefix('dashboard.');
// Returns: ['dashboard.theme' => '...', 'dashboard.sidebar_collapsed' => '...']

// Get all financial settings
$financialSettings = Setting::getByPrefix('financial.');
// Returns: ['financial.default_currency' => '...', 'financial.tax_rate' => '...']
```

### 2. Delete Settings by Prefix

```php
// Delete all temporary settings
$deletedCount = Setting::deleteByPrefix('temp.');
echo "Deleted {$deletedCount} temporary settings";

// Delete all cache settings
$deletedCount = Setting::deleteByPrefix('cache.');
echo "Deleted {$deletedCount} cache settings";
```

## Controller Usage Examples

### 1. In a Controller

```php
<?php

namespace App\Http\Controllers;

use App\Facades\Setting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get dashboard settings
        $theme = Setting::get('dashboard.theme', 'light');
        $pageSize = Setting::get('dashboard.default_page_size', 25);
        $sidebarCollapsed = Setting::get('dashboard.sidebar_collapsed', false);
        
        return view('dashboard', compact('theme', 'pageSize', 'sidebarCollapsed'));
    }
    
    public function updateSettings(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark',
            'page_size' => 'required|integer|min:10|max:100',
            'sidebar_collapsed' => 'boolean'
        ]);
        
        // Update settings
        Setting::setMultiple([
            'dashboard.theme' => $request->theme,
            'dashboard.default_page_size' => $request->page_size,
            'dashboard.sidebar_collapsed' => $request->boolean('sidebar_collapsed')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }
}
```

### 2. In a Service Class

```php
<?php

namespace App\Services;

use App\Facades\Setting;

class ConfigurationService
{
    public function getAppConfiguration(): array
    {
        return Setting::getMultiple([
            'app.name',
            'app.version',
            'app.environment',
            'app.timezone',
            'app.locale'
        ]);
    }
    
    public function updateAppConfiguration(array $config): bool
    {
        $settings = [];
        foreach ($config as $key => $value) {
            $settings["app.{$key}"] = $value;
        }
        
        return Setting::setMultiple($settings);
    }
    
    public function getFinancialSettings(): array
    {
        return Setting::getByPrefix('financial.');
    }
    
    public function resetToDefaults(): bool
    {
        $defaults = [
            'app.name' => 'Cashflow Management System',
            'app.version' => '1.0.0',
            'app.environment' => 'production',
            'dashboard.theme' => 'light',
            'dashboard.default_page_size' => 25,
            'financial.default_currency' => 'USD',
            'financial.tax_rate' => 0.10
        ];
        
        return Setting::setMultiple($defaults);
    }
}
```

## Blade Template Usage

### 1. In Blade Templates

```blade
{{-- Get setting value in blade --}}
<h1>{{ Helper::setting('app.name', 'My Application') }}</h1>

{{-- Use setting in conditional --}}
@if(Helper::setting('dashboard.theme') === 'dark')
    <div class="dark-theme">
        <!-- Dark theme content -->
    </div>
@else
    <div class="light-theme">
        <!-- Light theme content -->
    </div>
@endif

{{-- Use setting in loop --}}
@foreach(Helper::setting('app.supported_currencies', ['USD']) as $currency)
    <option value="{{ $currency }}">{{ $currency }}</option>
@endforeach
```

## API Usage Examples

### 1. Get All Settings

```javascript
// Frontend JavaScript
fetch('/api/settings', {
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('All settings:', data.data);
    }
});
```

### 2. Set a Setting

```javascript
// Set a single setting
fetch('/api/settings/set', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        key: 'dashboard.theme',
        value: 'dark'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Setting saved successfully');
    }
});
```

### 3. Set Multiple Settings

```javascript
// Set multiple settings
fetch('/api/settings/set-multiple', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        settings: {
            'dashboard.theme': 'dark',
            'dashboard.sidebar_collapsed': false,
            'dashboard.default_page_size': 50
        }
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Settings saved successfully');
    }
});
```

## Error Handling Examples

### 1. Handle Validation Errors

```php
try {
    Setting::set('invalid key!', 'value');
} catch (\Illuminate\Validation\ValidationException $e) {
    $errors = $e->errors();
    // Handle validation errors
    foreach ($errors as $field => $messages) {
        foreach ($messages as $message) {
            echo "Error in {$field}: {$message}\n";
        }
    }
}
```

### 2. Handle API Errors

```javascript
fetch('/api/settings/set', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        key: 'invalid key!',
        value: 'value'
    })
})
.then(response => response.json())
.then(data => {
    if (!data.success) {
        console.error('Error:', data.message);
        if (data.errors) {
            console.error('Validation errors:', data.errors);
        }
    }
});
```

## Testing Examples

### 1. Unit Test Example

```php
<?php

namespace Tests\Unit;

use App\Facades\Setting;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    public function test_can_set_and_get_setting()
    {
        Setting::set('test.key', 'test value');
        
        $this->assertEquals('test value', Setting::get('test.key'));
    }
    
    public function test_returns_default_when_key_not_found()
    {
        $value = Setting::get('non.existent.key', 'default value');
        
        $this->assertEquals('default value', $value);
    }
}
```

### 2. Feature Test Example

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    public function test_can_set_setting_via_api()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/settings/set', [
            'key' => 'test.key',
            'value' => 'test value'
        ]);
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
    }
}
```

These examples demonstrate the full range of capabilities of the Setting Service, from basic usage to advanced features like bulk operations, prefix filtering, and API integration.
