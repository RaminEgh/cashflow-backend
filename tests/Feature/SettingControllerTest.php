<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_can_get_all_settings()
    {
        Setting::create(['key' => 'app.name', 'value' => 'My App']);
        Setting::create(['key' => 'app.version', 'value' => '1.0.0']);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'app.name' => 'My App',
                        'app.version' => '1.0.0'
                    ]
                ]);
    }

    public function test_can_get_specific_setting()
    {
        Setting::create(['key' => 'app.name', 'value' => 'My App']);

        $response = $this->getJson('/api/settings/get?key=app.name');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'key' => 'app.name',
                        'value' => 'My App'
                    ]
                ]);
    }

    public function test_can_set_setting()
    {
        $response = $this->postJson('/api/settings/set', [
            'key' => 'app.name',
            'value' => 'My App'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Setting saved successfully',
                    'data' => [
                        'key' => 'app.name',
                        'value' => 'My App'
                    ]
                ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'app.name',
            'value' => 'My App'
        ]);
    }

    public function test_can_set_array_setting()
    {
        $arrayValue = ['key1' => 'value1', 'key2' => 'value2'];

        $response = $this->postJson('/api/settings/set', [
            'key' => 'app.config',
            'value' => $arrayValue
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('settings', [
            'key' => 'app.config',
            'value' => json_encode($arrayValue)
        ]);
    }

    public function test_can_get_multiple_settings()
    {
        Setting::create(['key' => 'key1', 'value' => 'value1']);
        Setting::create(['key' => 'key2', 'value' => 'value2']);

        $response = $this->postJson('/api/settings/get-multiple', [
            'keys' => ['key1', 'key2']
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'key1' => 'value1',
                        'key2' => 'value2'
                    ]
                ]);
    }

    public function test_can_set_multiple_settings()
    {
        $settings = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested' => 'value']
        ];

        $response = $this->postJson('/api/settings/set-multiple', [
            'settings' => $settings
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Settings saved successfully'
                ]);

        foreach ($settings as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
                'value' => is_string($value) ? $value : json_encode($value)
            ]);
        }
    }

    public function test_can_check_if_setting_exists()
    {
        Setting::create(['key' => 'existing_key', 'value' => 'value']);

        $response = $this->getJson('/api/settings/has?key=existing_key');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'key' => 'existing_key',
                        'exists' => true
                    ]
                ]);
    }

    public function test_can_check_if_setting_does_not_exist()
    {
        $response = $this->getJson('/api/settings/has?key=non_existent_key');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'key' => 'non_existent_key',
                        'exists' => false
                    ]
                ]);
    }

    public function test_can_delete_setting()
    {
        Setting::create(['key' => 'to_delete', 'value' => 'value']);

        $response = $this->deleteJson('/api/settings/delete?key=to_delete');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Setting deleted successfully'
                ]);

        $this->assertDatabaseMissing('settings', [
            'key' => 'to_delete'
        ]);
    }

    public function test_can_get_settings_by_prefix()
    {
        Setting::create(['key' => 'app.name', 'value' => 'My App']);
        Setting::create(['key' => 'app.version', 'value' => '1.0.0']);
        Setting::create(['key' => 'database.host', 'value' => 'localhost']);

        $response = $this->getJson('/api/settings/by-prefix?prefix=app.');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'app.name' => 'My App',
                        'app.version' => '1.0.0'
                    ]
                ]);
    }

    public function test_can_delete_settings_by_prefix()
    {
        Setting::create(['key' => 'app.name', 'value' => 'My App']);
        Setting::create(['key' => 'app.version', 'value' => '1.0.0']);
        Setting::create(['key' => 'database.host', 'value' => 'localhost']);

        $response = $this->deleteJson('/api/settings/by-prefix?prefix=app.');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Deleted 2 settings',
                    'data' => [
                        'deleted_count' => 2
                    ]
                ]);

        $this->assertDatabaseMissing('settings', ['key' => 'app.name']);
        $this->assertDatabaseMissing('settings', ['key' => 'app.version']);
        $this->assertDatabaseHas('settings', ['key' => 'database.host']);
    }

    public function test_can_clear_cache()
    {
        $response = $this->postJson('/api/settings/clear-cache');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Settings cache cleared successfully'
                ]);
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/settings/set', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['key', 'value']);
    }

    public function test_validates_key_format()
    {
        $response = $this->postJson('/api/settings/set', [
            'key' => 'invalid key!',
            'value' => 'value'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['key']);
    }

    public function test_returns_404_when_deleting_non_existent_setting()
    {
        $response = $this->deleteJson('/api/settings/delete?key=non_existent');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Setting not found or could not be deleted'
                ]);
    }

    public function test_requires_authentication()
    {
        // Logout the user
        auth()->logout();

        $response = $this->getJson('/api/settings');

        $response->assertStatus(401);
    }
}
