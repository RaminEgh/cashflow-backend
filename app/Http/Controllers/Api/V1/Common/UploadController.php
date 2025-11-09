<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Upload\StoreUploadRequest;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Http\Resources\V1\Common\UploadResource;
use App\Models\Upload;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __construct(protected UploadService $uploadService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Upload::query()
            ->where('user_id', Auth::id())
            ->with('user:id,first_name,last_name,email')
            ->latest();

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
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $uploads = $query->paginate($perPage);
        
        return Helper::successResponse(null, [
            'list' => UploadResource::collection($uploads),
            'pagination' => new PaginationCollection($uploads)
        ]);
    }

    public function store(StoreUploadRequest $request): JsonResponse
    {
        try {
            $upload = $this->uploadService->store(
                $request->file('file'),
                [
                    'user_id' => Auth::id(),
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                    'is_private' => $request->boolean('is_private', false),
                ]
            );

            return Helper::successResponse(
                __('crud.d_created', ['source' => __('sources.upload'), 'name' => $upload->original_name]),
                new UploadResource($upload->load('user:id,first_name,last_name,email'))
            );
        } catch (\Exception $e) {
            return Helper::errorResponse($e->getMessage());
        }
    }

    public function show(Upload $upload): JsonResponse
    {
        Gate::authorize('view', $upload);

        return Helper::successResponse(
            null,
            new UploadResource($upload->load('user:id,first_name,last_name,email'))
        );
    }

    public function download(Upload $upload)
    {
        // Private uploads require authentication and authorization
        if ($upload->is_private === Upload::PRIVATE_UPLOAD) {
            if (! Auth::check()) {
                abort(401, 'Authentication required.');
            }
            Gate::authorize('view', $upload);
        }

        $disk = $upload->disk ?? ($upload->is_private ? 'private_uploads' : 'public_uploads');

        if (! Storage::disk($disk)->exists($upload->path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk($disk)->download($upload->path, $upload->original_name);
    }

    public function display(Upload $upload)
    {
        // Private uploads require authentication and authorization
        if ($upload->is_private === Upload::PRIVATE_UPLOAD) {
            if (! Auth::check()) {
                abort(401, 'Authentication required.');
            }
            Gate::authorize('view', $upload);
        }

        $disk = $upload->disk ?? ($upload->is_private ? 'private_uploads' : 'public_uploads');

        if (! Storage::disk($disk)->exists($upload->path)) {
            abort(404, 'File not found.');
        }

        $file = Storage::disk($disk)->get($upload->path);

        return response($file, 200)
            ->header('Content-Type', $upload->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $upload->original_name . '"');
    }
}
