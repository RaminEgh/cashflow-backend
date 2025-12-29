<?php

namespace App\Enums;

enum UserType: int
{
    case Unknown = 0;
    case Admin = 1;
    case Organ = 2;
    case General = 3;

    /**
     * Get all user type values as an array.
     *
     * @return array<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all user types as key-value pairs.
     *
     * @return array<string, int>
     */
    public static function toArray(): array
    {
        return [
            'unknown' => self::Unknown->value,
            'admin' => self::Admin->value,
            'organ' => self::Organ->value,
            'general' => self::General->value,
        ];
    }

    /**
     * Get the string name for the user type.
     */
    public function name(): string
    {
        return match ($this) {
            self::Unknown => 'unknown',
            self::Admin => 'admin',
            self::Organ => 'organ',
            self::General => 'general',
        };
    }

    /**
     * Get user type from string name.
     */
    public static function fromName(string $name): self
    {
        return match ($name) {
            'unknown' => self::Unknown,
            'admin' => self::Admin,
            'organ' => self::Organ,
            'general' => self::General,
            default => self::Unknown,
        };
    }

    /**
     * Get the Persian label for the user type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'نامشخص',
            self::Admin => 'ادمین',
            self::Organ => 'سازمان',
            self::General => 'عمومی',
        };
    }
}
