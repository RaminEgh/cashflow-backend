<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allocation extends Model
{
    protected $guarded = ['id'];

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }
}
