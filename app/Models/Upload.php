<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Upload extends Model
{
    /** @use HasFactory<\Database\Factories\UploadFactory> */
    use HasFactory;

    const PUBLIC_UPLOAD = 0;

    const PRIVATE_UPLOAD = 1;

    protected $fillable = [
        'slug',
        'user_id',
        'title',
        'description',
        'original_name',
        'stored_name',
        'mime_type',
        'size',
        'path',
        'disk',
    ];

    public function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                // For public uploads, use direct storage URL if available
                if ($this->is_private === self::PUBLIC_UPLOAD) {
                    $disk = $this->disk ?? 'public_uploads';

                    return Storage::disk($disk)->url($this->path);
                }

                // For private uploads, use the download route
                return url("/api/upload/{$this->slug}/download");
            }
        );
    }

    public function humanReadableSize(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bytes = $this->size;
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];

                for ($i = 0; $bytes > 1024; $i++) {
                    $bytes /= 1024;
                }

                return round($bytes, 2).' '.$units[$i];
            }
        );
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isDocument(): bool
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return in_array($this->mime_type, $documentMimes);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', self::PUBLIC_UPLOAD);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', self::PRIVATE_UPLOAD);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function deleteFile(): bool
    {
        $disk = $this->disk ?? ($this->is_private ? 'private_uploads' : 'public_uploads');

        if (Storage::disk($disk)->exists($this->path)) {
            return Storage::disk($disk)->delete($this->path);
        }

        return false;
    }

    protected static function booted(): void
    {
        static::deleting(function (Upload $upload) {
            $upload->deleteFile();
        });
    }
}
