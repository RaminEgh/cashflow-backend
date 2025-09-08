<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    /** @use HasFactory<\Database\Factories\UploadFactory> */
    use HasFactory;

    const PUBLIC_UPLOAD = 0;
    const PRIVATE_UPLOAD = 1;

    protected $fillable = [
        'slug', 'user_id', 'title', 'description', 'is_private', 'original_name', 'stored_name', 'mime_type', 'size', 'path'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
