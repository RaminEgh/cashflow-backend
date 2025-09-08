<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deposit extends Model
{
    /** @use HasFactory<\Database\Factories\DepositFactory> */
    use HasFactory;
    protected $guarded = ['id'];

    const DEPOSIT_CURRENT = 1;
    const DEPOSIT_CURRENT_QARZ = 2;
    const DEPOSIT_SAVING_QARZ = 3;
    const DEPOSIT_LONG_TERM_INVESTMENT = 4;
    const DEPOSIT_SHORT_TERM_INVESTMENT = 5;
    const DEPOSIT_JOINT = 6;
    const DEPOSIT_FOREIGN_CURRENCY = 7;
    const DEPOSIT_FIDUCIARY = 8;
    const DEPOSIT_TYPES = [
        self::DEPOSIT_CURRENT,
        self::DEPOSIT_CURRENT_QARZ,
        self::DEPOSIT_SAVING_QARZ,
        self::DEPOSIT_LONG_TERM_INVESTMENT,
        self::DEPOSIT_SHORT_TERM_INVESTMENT,
        self::DEPOSIT_JOINT,
        self::DEPOSIT_FOREIGN_CURRENCY,
        self::DEPOSIT_FIDUCIARY
    ];


    const DEPOSITS_KEY_VALUE = [
        [
            "id" => self::DEPOSIT_CURRENT,
            "name" => "حساب جاری",
        ],
        [
            "id" => self::DEPOSIT_CURRENT_QARZ,
            "name" => "حساب قرض الحسنه جاری",
        ],
        [
            "id" => self::DEPOSIT_SAVING_QARZ,
            "name" => "حساب قرض الحسنه پس انداز",
        ],
        [
            "id" => self::DEPOSIT_LONG_TERM_INVESTMENT,
            "name" => "حساب سرمایه گذاری بلندمدت",
        ],
        [
            "id" => self::DEPOSIT_SHORT_TERM_INVESTMENT,
            "name" => "حساب سرمایه گذاری کوتاه مدت",
        ],
        [
            "id" => self::DEPOSIT_JOINT,
            "name" => "حساب اشتراکی",
        ],
        [
            "id" => self::DEPOSIT_FOREIGN_CURRENCY,
            "name" => "حساب ارزی",
        ],
        [
            "id" => self::DEPOSIT_FIDUCIARY,
            "name" => "حساب وکالتی",
        ]
    ];

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(Balance::class);
    }


}
