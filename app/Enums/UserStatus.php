<?php

namespace App\Enums;

enum UserStatus: int
{
    case Inactive = 0;
    case Active = 1;
    case Blocked = 2;

    /**
     * Get all user status values as an array.
     *
     * @return array<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all user statuses as key-value pairs with names.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public static function keyValue(): array
    {
        return [
            [
                'id' => self::Inactive->value,
                'name' => 'Inactive',
            ],
            [
                'id' => self::Active->value,
                'name' => 'Active',
            ],
            [
                'id' => self::Blocked->value,
                'name' => 'Blocked',
            ],
        ];
    }

    /**
     * Get the string name for the user status.
     */
    public function name(): string
    {
        return match ($this) {
            self::Inactive => 'inactive',
            self::Active => 'active',
            self::Blocked => 'blocked',
        };
    }
}
