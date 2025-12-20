<?php

namespace Tests\Unit;

use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SettingService $settingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingService = new SettingService;
    }

    public function test_can_set_and_get_string_setting()
    {
        $this->settingService->set('test_key', 'test_value');

        $this->assertEquals('test_value', $this->settingService->get('test_key'));
    }

    public function test_can_set_and_get_array_setting()
    {
        $arrayValue = ['key1' => 'value1', 'key2' => 'value2'];
        $this->settingService->set('array_key', $arrayValue);

        $this->assertEquals($arrayValue, $this->settingService->get('array_key'));
    }

    public function test_can_set_and_get_numeric_setting()
    {
        $this->settingService->set('numeric_key', 123);

        $this->assertEquals(123, $this->settingService->get('numeric_key'));
    }

    public function test_can_set_and_get_boolean_setting()
    {
        $this->settingService->set('boolean_key', true);

        $this->assertTrue($this->settingService->get('boolean_key'));
    }

    public function test_returns_default_when_key_not_found()
    {
        $this->assertEquals('default_value', $this->settingService->get('non_existent_key', 'default_value'));
    }

    public function test_can_set_multiple_settings()
    {
        $settings = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested' => 'value'],
        ];

        $this->assertTrue($this->settingService->setMultiple($settings));

        $this->assertEquals('value1', $this->settingService->get('key1'));
        $this->assertEquals('value2', $this->settingService->get('key2'));
        $this->assertEquals(['nested' => 'value'], $this->settingService->get('key3'));
    }

    public function test_can_get_multiple_settings()
    {
        $this->settingService->set('key1', 'value1');
        $this->settingService->set('key2', 'value2');
        $this->settingService->set('key3', 'value3');

        $result = $this->settingService->getMultiple(['key1', 'key2', 'key3']);

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ], $result);
    }

    public function test_can_check_if_setting_exists()
    {
        $this->assertFalse($this->settingService->has('non_existent_key'));

        $this->settingService->set('existing_key', 'value');
        $this->assertTrue($this->settingService->has('existing_key'));
    }

    public function test_can_delete_setting()
    {
        $this->settingService->set('to_delete', 'value');
        $this->assertTrue($this->settingService->has('to_delete'));

        $this->assertTrue($this->settingService->delete('to_delete'));
        $this->assertFalse($this->settingService->has('to_delete'));
    }

    public function test_can_get_all_settings()
    {
        $this->settingService->set('key1', 'value1');
        $this->settingService->set('key2', 'value2');

        $allSettings = $this->settingService->all();

        $this->assertArrayHasKey('key1', $allSettings);
        $this->assertArrayHasKey('key2', $allSettings);
        $this->assertEquals('value1', $allSettings['key1']);
        $this->assertEquals('value2', $allSettings['key2']);
    }

    public function test_can_get_settings_by_prefix()
    {
        $this->settingService->set('app.name', 'My App');
        $this->settingService->set('app.version', '1.0.0');
        $this->settingService->set('database.host', 'localhost');

        $appSettings = $this->settingService->getByPrefix('app.');

        $this->assertArrayHasKey('app.name', $appSettings);
        $this->assertArrayHasKey('app.version', $appSettings);
        $this->assertArrayNotHasKey('database.host', $appSettings);
        $this->assertEquals('My App', $appSettings['app.name']);
        $this->assertEquals('1.0.0', $appSettings['app.version']);
    }

    public function test_can_delete_settings_by_prefix()
    {
        $this->settingService->set('app.name', 'My App');
        $this->settingService->set('app.version', '1.0.0');
        $this->settingService->set('database.host', 'localhost');

        $deletedCount = $this->settingService->deleteByPrefix('app.');

        $this->assertEquals(2, $deletedCount);
        $this->assertFalse($this->settingService->has('app.name'));
        $this->assertFalse($this->settingService->has('app.version'));
        $this->assertTrue($this->settingService->has('database.host'));
    }

    public function test_uses_cache_for_performance()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn('cached_value');

        $this->settingService->get('cached_key');
    }

    public function test_clears_cache_when_setting_updated()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('setting_test_key');

        $this->settingService->set('test_key', 'new_value');
    }

    public function test_clears_cache_when_setting_deleted()
    {
        $this->settingService->set('to_delete', 'value');

        Cache::shouldReceive('forget')
            ->once()
            ->with('setting_to_delete');

        $this->settingService->delete('to_delete');
    }

    public function test_validates_setting_key_format()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->settingService->set('invalid key!', 'value');
    }

    public function test_allows_valid_key_formats()
    {
        $validKeys = [
            'valid_key',
            'valid-key',
            'valid.key',
            'valid_key123',
            'app.config.setting',
        ];

        foreach ($validKeys as $key) {
            $this->assertTrue($this->settingService->set($key, 'value'));
            $this->assertEquals('value', $this->settingService->get($key));
        }
    }
}
