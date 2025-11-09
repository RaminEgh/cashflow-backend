<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Common\UploadResource;
use App\Models\Upload;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(protected UploadService $uploadService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Upload::query()
            ->with('user:id,first_name,last_name,email')
            ->latest();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('type')) {
            match ($request->input('type')) {
                'images' => $query->images(),
                'documents' => $query->documents(),
                default => null,
            };
        }

        if ($request->has('is_private')) {
            $isPrivate = $request->boolean('is_private');
            $query->where('is_private', $isPrivate ? Upload::PRIVATE_UPLOAD : Upload::PUBLIC_UPLOAD);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->input('per_page', 15);
        $uploads = $query->paginate($perPage);

        return response()->json([
            'data' => UploadResource::collection($uploads),
            'meta' => [
                'current_page' => $uploads->currentPage(),
                'last_page' => $uploads->lastPage(),
                'per_page' => $uploads->perPage(),
                'total' => $uploads->total(),
            ],
        ]);
    }

    public function show(Upload $upload): JsonResponse
    {
        return Helper::successResponse(
            null,
            new UploadResource($upload->load('user:id,first_name,last_name,email'))
        );
    }

    public function destroy(Upload $upload): JsonResponse
    {
        $upload->delete();

        return response()->json([
            'message' => 'File deleted successfully.',
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'upload_ids' => ['required', 'array', 'min:1'],
            'upload_ids.*' => ['required', 'integer', 'exists:uploads,id'],
        ]);

        $uploads = Upload::whereIn('id', $request->input('upload_ids'))->get();

        $deleted = 0;
        foreach ($uploads as $upload) {
            if ($upload->delete()) {
                $deleted++;
            }
        }

        return response()->json([
            'message' => "{$deleted} file(s) deleted successfully.",
            'deleted_count' => $deleted,
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $stats = $this->uploadService->getStorageStatistics($userId);

        return response()->json([
            'data' => $stats,
        ]);
    }

    public function userStatistics(): JsonResponse
    {
        $userStats = Upload::query()
            ->selectRaw('user_id, COUNT(*) as total_files, SUM(size) as total_size')
            ->with('user:id,first_name,last_name,email')
            ->groupBy('user_id')
            ->orderByDesc('total_size')
            ->get()
            ->map(function ($stat) {
                return [
                    'user' => [
                        'id' => $stat->user?->id,
                        'name' => $stat->user?->name,
                        'email' => $stat->user?->email,
                    ],
                    'total_files' => $stat->total_files,
                    'total_size' => $stat->total_size,
                    'total_size_human' => $this->formatBytes($stat->total_size),
                ];
            });

        return response()->json([
            'data' => $userStats,
        ]);
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
