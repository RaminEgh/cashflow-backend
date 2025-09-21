<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller;

class SettingController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Get a specific setting
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string'
        ]);

        $value = $this->settingService->get($request->key);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $request->key,
                'value' => $value
            ]
        ]);
    }

    /**
     * Set a specific setting
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function set(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:255|regex:/^[a-zA-Z0-9._-]+$/',
            'value' => 'required'
        ]);

        try {
            $success = $this->settingService->set($request->key, $request->value);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Setting saved successfully',
                    'data' => [
                        'key' => $request->key,
                        'value' => $request->value
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to save setting'
            ], 500);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Get multiple settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'keys' => 'required|array',
            'keys.*' => 'string'
        ]);

        $settings = $this->settingService->getMultiple($request->keys);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Set multiple settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required'
        ]);

        try {
            $success = $this->settingService->setMultiple($request->settings);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Settings saved successfully',
                    'data' => $request->settings
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to save some settings'
            ], 500);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Get all settings
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        $settings = $this->settingService->all();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Check if a setting exists
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function has(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string'
        ]);

        $exists = $this->settingService->has($request->key);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $request->key,
                'exists' => $exists
            ]
        ]);
    }

    /**
     * Delete a setting
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string'
        ]);

        $deleted = $this->settingService->delete($request->key);

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Setting deleted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Setting not found or could not be deleted'
        ], 404);
    }

    /**
     * Get settings by prefix
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByPrefix(Request $request): JsonResponse
    {
        $request->validate([
            'prefix' => 'required|string'
        ]);

        $settings = $this->settingService->getByPrefix($request->prefix);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Delete settings by prefix
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteByPrefix(Request $request): JsonResponse
    {
        $request->validate([
            'prefix' => 'required|string'
        ]);

        $deletedCount = $this->settingService->deleteByPrefix($request->prefix);

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} settings",
            'data' => [
                'deleted_count' => $deletedCount
            ]
        ]);
    }

    /**
     * Clear all settings cache
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        $this->settingService->clearAllCache();

        return response()->json([
            'success' => true,
            'message' => 'Settings cache cleared successfully'
        ]);
    }
}
