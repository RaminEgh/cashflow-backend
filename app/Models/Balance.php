<?php

namespace App\Models;

use Database\Factories\BalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    /** @use HasFactory<BalanceFactory> */
    use HasFactory;

    protected $guarded = ['id'];


    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }
}
