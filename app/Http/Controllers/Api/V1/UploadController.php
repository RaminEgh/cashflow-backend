<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Upload\StoreUploadRequest;
use App\Http\Resources\V1\Common\UploadResource;
use App\Models\Upload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UploadController extends Controller
{
    public function index()
    {
        $uploads = Upload::where('user_id', Auth::id())->get();

        return response()->json([
            'data' => $uploads
        ]);
    }


    public function store(StoreUploadRequest $request)
    {
        $file = $request->file('file');
        $disk = $request->boolean('is_private') ? 'private' : 'public';
        $isPrivate = $request->has('is_private') ? $request->boolean('is_private') : Upload::PUBLIC_UPLOAD;

        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('', $storedName, $disk );
        $upload = Upload::create([
            'user_id'       => Auth::id(),
            'slug'          => now()->format('Y-m-d') . Str::random(8),
            'title'         => $request->title,
            'description'   => $request->description,
            'is_private'    => $isPrivate ? '1' : '2',
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'mime_type'     => $file->getClientMimeType(),
            'size'          => $file->getSize(),
            'path'          => $path,
        ]);

        return Helper::successResponse(__('upload.crud', ['resource' => 'Upload', 'name' => $upload->name]), new UploadResource($upload));
    }

    public function show(Upload $upload)
    {
        return Helper::successResponse(null, $upload);
    }


    public function download(Upload $upload)
    {

        if ($upload->is_private && $upload->user_id !== Auth::id()) {
            abort(403);
        }

        $disk = $upload->is_private ? 'private_uploads' : 'public_uploads';
        return Storage::disk($disk)->download($upload->path, $upload->original_name);
    }

    public function update(UpdateUploadRequest $request, Upload $upload)
    {
        //
    }

    public function destroy(Upload $upload)
    {
        Storage::delete($upload->path);
        $upload->delete();

        return response()->json(['message' => 'File deleted successfully.']);
    }
}
