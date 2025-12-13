<?php

namespace App\Enums;

enum DepositType: int
{
    case Current = 1;
    case CurrentQarz = 2;
    case SavingQarz = 3;
    case LongTermInvestment = 4;
    case ShortTermInvestment = 5;
    case Joint = 6;
    case ForeignCurrency = 7;
    case Fiduciary = 8;

    /**
     * Get all deposit type values as an array.
     *
     * @return array<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all deposit types as key-value pairs with names.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public static function keyValue(): array
    {
        return [
            [
                'id' => self::Current->value,
                'name' => 'حساب جاری',
            ],
            [
                'id' => self::CurrentQarz->value,
                'name' => 'حساب قرض الحسنه جاری',
            ],
            [
                'id' => self::SavingQarz->value,
                'name' => 'حساب قرض الحسنه پس انداز',
            ],
            [
                'id' => self::LongTermInvestment->value,
                'name' => 'حساب سرمایه گذاری بلندمدت',
            ],
            [
                'id' => self::ShortTermInvestment->value,
                'name' => 'حساب سرمایه گذاری کوتاه مدت',
            ],
            [
                'id' => self::Joint->value,
                'name' => 'حساب اشتراکی',
            ],
            [
                'id' => self::ForeignCurrency->value,
                'name' => 'حساب ارزی',
            ],
            [
                'id' => self::Fiduciary->value,
                'name' => 'حساب وکالتی',
            ],
        ];
    }

    /**
     * Get the Persian name for the deposit type.
     */
    public function name(): string
    {
        return match ($this) {
            self::Current => 'حساب جاری',
            self::CurrentQarz => 'حساب قرض الحسنه جاری',
            self::SavingQarz => 'حساب قرض الحسنه پس انداز',
            self::LongTermInvestment => 'حساب سرمایه گذاری بلندمدت',
            self::ShortTermInvestment => 'حساب سرمایه گذاری کوتاه مدت',
            self::Joint => 'حساب اشتراکی',
            self::ForeignCurrency => 'حساب ارزی',
            self::Fiduciary => 'حساب وکالتی',
        };
    }
}
