<?php

namespace App\Services;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class UploadService
{
    public function store(UploadedFile $file, array $data = []): Upload
    {
        $isPrivate = $data['is_private'] ?? false;
        $disk = $isPrivate ? config('upload.private_disk') : config('upload.default_disk');

        $storedName = $this->generateFileName($file);
        $path = $this->getStoragePath($data['user_id'] ?? auth()->id());

        $fullPath = $file->storeAs($path, $storedName, $disk);

        return Upload::create([
            'user_id' => $data['user_id'] ?? auth()->id(),
            'slug' => $this->generateUniqueSlug(),
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'is_private' => $isPrivate ? Upload::PRIVATE_UPLOAD : Upload::PUBLIC_UPLOAD,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $fullPath,
            'disk' => $disk,
        ]);
    }

    public function bulkDelete(array $uploadIds, User $user): int
    {
        $uploads = Upload::query()
            ->whereIn('id', $uploadIds)
            ->where('user_id', $user->id)
            ->get();

        $deleted = 0;
        foreach ($uploads as $upload) {
            if ($upload->delete()) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function createZipArchive(array $uploadIds, User $user): ?string
    {
        $uploads = Upload::query()
            ->whereIn('id', $uploadIds)
            ->where('user_id', $user->id)
            ->get();

        if ($uploads->isEmpty()) {
            return null;
        }

        $zipFileName = 'uploads-'.now()->format('Y-m-d-His').'.zip';
        $zipPath = storage_path('app/temp/'.$zipFileName);

        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return null;
        }

        foreach ($uploads as $upload) {
            $disk = $upload->disk ?? ($upload->is_private ? 'private_uploads' : 'public_uploads');
            $filePath = Storage::disk($disk)->path($upload->path);

            if (file_exists($filePath)) {
                $zip->addFile($filePath, $upload->original_name);
            }
        }

        $zip->close();

        return $zipPath;
    }

    public function getStorageStatistics(?int $userId = null): array
    {
        $query = Upload::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $totalFiles = $query->count();
        $totalSize = $query->sum('size');
        $imageCount = (clone $query)->images()->count();
        $documentCount = (clone $query)->documents()->count();
        $publicCount = (clone $query)->public()->count();
        $privateCount = (clone $query)->private()->count();

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'images_count' => $imageCount,
            'documents_count' => $documentCount,
            'other_count' => $totalFiles - $imageCount - $documentCount,
            'public_count' => $publicCount,
            'private_count' => $privateCount,
            'average_file_size' => $totalFiles > 0 ? round($totalSize / $totalFiles) : 0,
            'average_file_size_human' => $totalFiles > 0 ? $this->formatBytes(round($totalSize / $totalFiles)) : '0 B',
        ];
    }

    protected function generateFileName(UploadedFile $file): string
    {
        return Str::uuid().'.'.$file->getClientOriginalExtension();
    }

    protected function generateUniqueSlug(): string
    {
        do {
            $slug = Str::slug(now()->format('Y-m-d').'-'.Str::random(8));
        } while (Upload::where('slug', $slug)->exists());

        return $slug;
    }

    protected function getStoragePath(int $userId): string
    {
        return now()->format('Y/m').'/'.$userId;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    public function cleanupTempFiles(): void
    {
        $tempPath = storage_path('app/temp');

        if (! file_exists($tempPath)) {
            return;
        }

        $files = glob($tempPath.'/*.zip');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) >= 3600)) {
                unlink($file);
            }
        }
    }
}
