<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEntry extends Model
{
    /** @use HasFactory<\Database\Factories\TimelineEntryFactory> */
    use HasFactory;

    const TYPE_INCOME = 'income';

    const TYPE_EXPENSE = 'expense';

    const TYPES = [
        self::TYPE_INCOME => 'Income',
        self::TYPE_EXPENSE => 'Expense',
    ];

    protected $guarded = ['id'];

    protected $fillable = [
        'organ_id',
        'type',
        'title',
        'date',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }

    public function getTypeName(): string
    {
        return self::TYPES[$this->type] ?? 'Unknown';
    }
}
