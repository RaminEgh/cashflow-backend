<?php

namespace App\Constants;

class OrganPermissionKey
{
    const ORGAN = 'organ-root';

    const BANK = 'organ-bank';

    const BANK_LIST = 'organ-bank-list';

    const BANK_CREATE = 'organ-bank-create';

    const BANK_SHOW = 'organ-bank-show';

    const BANK_EDIT = 'organ-bank-edit';

    const BANK_DELETE = 'organ-bank-delete';

    const DEPOSIT = 'organ-deposit';

    const DEPOSIT_LIST = 'organ-deposit-list';

    const DEPOSIT_CREATE = 'organ-deposit-create';

    const DEPOSIT_SHOW = 'organ-deposit-show';

    const DEPOSIT_EDIT = 'organ-deposit-edit';

    const DEPOSIT_DELETE = 'organ-deposit-delete';

    const PARENT_PERMISSIONS = [
        self::ORGAN => 'دسترسی به پنل سازمان',
        self::BANK => 'دسترسی به بانک ها',
        self::DEPOSIT => 'دسترسی به حساب ها',
    ];

    const PERMISSIONS = [
        self::ORGAN => 'پنل سازمان',

        self::BANK => 'بانک',
        self::BANK_LIST => 'لیست بانک ها',
        self::BANK_SHOW => 'نمایش بانک',
        self::BANK_EDIT => 'ویرایش بانک',
        self::BANK_CREATE => 'ایجاد بانک',
        self::BANK_DELETE => 'حذف بانک',

        self::DEPOSIT => 'سپرده',
        self::DEPOSIT_LIST => 'لیست سپرده ها',
        self::DEPOSIT_SHOW => 'نمایش سپرده',
        self::DEPOSIT_EDIT => 'ویرایش سپرده',
        self::DEPOSIT_CREATE => 'ایجاد سپرده',
        self::DEPOSIT_DELETE => 'حذف سپرده',
    ];
}
