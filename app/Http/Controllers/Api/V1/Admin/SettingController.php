<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Get a specific setting
     */
    public function get(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $value = $this->settingService->get($request->key);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $request->key,
                'value' => $value,
            ],
        ]);
    }

    /**
     * Set a specific setting
     */
    public function set(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:255|regex:/^[a-zA-Z0-9._-]+$/',
            'value' => 'required',
        ]);

        try {
            $success = $this->settingService->set($request->key, $request->value);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Setting saved successfully',
                    'data' => [
                        'key' => $request->key,
                        'value' => $request->value,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to save setting',
            ], 500);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get multiple settings
     */
    public function getMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'keys' => 'required|array',
            'keys.*' => 'string',
        ]);

        $settings = $this->settingService->getMultiple($request->keys);

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Set multiple settings
     */
    public function setMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required',
        ]);

        try {
            $success = $this->settingService->setMultiple($request->settings);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Settings saved successfully',
                    'data' => $request->settings,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to save some settings',
            ], 500);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get all settings
     */
    public function all(): JsonResponse
    {
        $settings = $this->settingService->all();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Check if a setting exists
     */
    public function has(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $exists = $this->settingService->has($request->key);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $request->key,
                'exists' => $exists,
            ],
        ]);
    }

    /**
     * Delete a setting
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $deleted = $this->settingService->delete($request->key);

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Setting deleted successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Setting not found or could not be deleted',
        ], 404);
    }

    /**
     * Get settings by prefix
     */
    public function getByPrefix(Request $request): JsonResponse
    {
        $request->validate([
            'prefix' => 'required|string',
        ]);

        $settings = $this->settingService->getByPrefix($request->prefix);

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Delete settings by prefix
     */
    public function deleteByPrefix(Request $request): JsonResponse
    {
        $request->validate([
            'prefix' => 'required|string',
        ]);

        $deletedCount = $this->settingService->deleteByPrefix($request->prefix);

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} settings",
            'data' => [
                'deleted_count' => $deletedCount,
            ],
        ]);
    }

    /**
     * Clear all settings cache
     */
    public function clearCache(): JsonResponse
    {
        $this->settingService->clearAllCache();

        return response()->json([
            'success' => true,
            'message' => 'Settings cache cleared successfully',
        ]);
    }

    /**
     * Get all environment variables from .env file
     * Masks sensitive values (passwords, secrets, tokens, keys)
     */
    public function getEnvVariables(): JsonResponse
    {
        $envFile = base_path('.env');

        if (! file_exists($envFile)) {
            return response()->json([
                'success' => false,
                'message' => '.env file not found',
            ], 404);
        }

        $envContent = file_get_contents($envFile);
        $lines = explode("\n", $envContent);

        $envVars = [];
        $sensitiveKeywords = ['password', 'secret', 'token', 'key', 'api_key', 'private', 'credential'];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse key=value
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present (handles both single and double quotes)
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                // Check if this is a sensitive variable
                $isSensitive = false;
                $keyLower = strtolower($key);
                foreach ($sensitiveKeywords as $keyword) {
                    if (str_contains($keyLower, $keyword)) {
                        $isSensitive = true;
                        break;
                    }
                }

                // Mask sensitive values
                if ($isSensitive && ! empty($value)) {
                    $valueLength = strlen($value);
                    if ($valueLength <= 4) {
                        $maskedValue = str_repeat('*', $valueLength);
                    } else {
                        $maskedValue = str_repeat('*', $valueLength - 4) . substr($value, -4);
                    }
                    $envVars[$key] = [
                        'value' => $maskedValue,
                        'is_masked' => true,
                        'original_length' => $valueLength,
                    ];
                } else {
                    $envVars[$key] = [
                        'value' => $value,
                        'is_masked' => false,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => count($envVars),
                'masked_count' => count(array_filter($envVars, fn($var) => $var['is_masked'])),
                'variables' => $envVars,
            ],
        ]);
    }
}
