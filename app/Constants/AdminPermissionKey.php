<?php

namespace App\Constants;

class AdminPermissionKey
{

    CONST ADMIN = 'admin-root';

    CONST ADMIN_ADMIN = 'admin-admin';
    CONST ADMIN_ADMIN_LIST = 'admin-admin-list';
    CONST ADMIN_ADMIN_SHOW = 'admin-admin-show';
    CONST ADMIN_ADMIN_CREATE = 'admin-admin-create';
    CONST ADMIN_ADMIN_EDIT = 'admin-admin-edit';
    CONST ADMIN_ADMIN_DELETE = 'admin-admin-delete';

    const USER = 'admin-user';
    const USER_LIST = 'admin-user-list';
    const USER_CREATE = 'admin-user-create';
    const USER_SHOW = 'admin-user-show';
    const USER_EDIT = 'admin-user-edit';
    const USER_DELETE = 'admin-user-delete';
    const USER_BLOCK = 'admin-user-block';
    const USER_UNBLOCK = 'admin-user-unblock';

    const ALLOCATION = 'admin-allocation';
    const ALLOCATION_LIST = 'admin-allocation-list';
    const ALLOCATION_CREATE = 'admin-allocation-create';
    const ALLOCATION_EDIT = 'admin-allocation-edit';
    const ALLOCATION_DELETE = 'admin-allocation-delete';
    const ALLOCATION_SHOW = 'admin-allocation-show';

    const BANK = 'admin-bank';
    const BANK_LIST = 'admin-bank-list';
    const BANK_CREATE = 'admin-bank-create';
    const BANK_SHOW = 'admin-bank-show';
    const BANK_EDIT = 'admin-bank-edit';
    const BANK_DELETE = 'admin-bank-delete';

    const ORGAN = 'admin-organ';
    const ORGAN_LIST = 'admin-organ-list';
    const ORGAN_CREATE = 'admin-organ-create';
    const ORGAN_SHOW = 'admin-organ-show';
    const ORGAN_EDIT = 'admin-organ-edit';
    const ORGAN_DELETE = 'admin-organ-delete';

    const ORGAN_ASSIGN_ADMIN = 'admin-organ-assign-admin';

    const DEPOSIT = 'admin-deposit';
    const DEPOSIT_LIST = 'admin-deposit-list';
    const DEPOSIT_CREATE = 'admin-deposit-create';
    const DEPOSIT_SHOW = 'admin-deposit-show';
    const DEPOSIT_EDIT = 'admin-deposit-edit';
    const DEPOSIT_DELETE = 'admin-deposit-delete';



    const ACCESS = 'admin-access';
    const ACCESS_LIST = 'admin-access-list';
    const ACCESS_ASSIGN = 'admin-access-assign';


    const ROLE = 'admin-role';
    const ROLE_LIST = 'admin-role-list';
    const ROLE_CREATE = 'admin-role-create';
    const ROLE_EDIT = 'admin-role-edit';
    const ROLE_DELETE = 'admin-role-delete';
    const ROLE_SHOW = 'admin-role-show';
    const PERMISSION = 'admin-permission';
    const PERMISSION_LIST = 'admin-permission-list';

    const PERMISSION_SHOW = 'admin-permission-show';
    const PERMISSION_EDIT = 'admin-permission-edit';

    const SETTING = 'admin-setting';
    const SETTING_LIST = 'admin-setting-list';
    const SETTING_EDIT = 'admin-setting-edit';
    const SETTING_SHOW = 'admin-setting-show';
    const SETTING_CREATE = 'admin-setting-create';
    const SETTING_DELETE = 'admin-setting-delete';


    const PARENT_PERMISSIONS = [
        self::ADMIN => 'دسترسی به پنل ادمین',
        self::ADMIN_ADMIN => 'دسترسی کاربران ادمین',
        self::USER => 'دسترسی کاربران',
        self::ORGAN => 'دسترسی سازمان ها',
        self::ALLOCATION => 'دسترسی بودجه/هزینه',
        self::BANK => 'دسترسی بانک ها',
        self::DEPOSIT => 'دسترسی حساب ها',
        self::ACCESS => 'دسترسی کاربران مدیریتی',
        self::ROLE => 'دسترسی نقش ها',
        self::PERMISSION => 'مشاهده لیست دسترسی های سایت',
        self::SETTING => 'دسترسی تنظیمات',
    ];


    const PERMISSIONS = [
        self::ADMIN => 'پنل ادمین',

        self::ADMIN_ADMIN => 'ادمین ها',
        self::ADMIN_ADMIN_LIST => 'لیست کاربر ادمین',
        self::ADMIN_ADMIN_SHOW => 'مشاهده کاربر ادمین',
        self::ADMIN_ADMIN_CREATE => 'ایجاد کاربر ادمین',
        self::ADMIN_ADMIN_EDIT => 'ویرایش کاربر ادمین',
        self::ADMIN_ADMIN_DELETE => 'حذف کاربر ادمین',

        self::USER => 'کاربران',
        self::USER_LIST => 'مشاهده لیست کاربران',
        self::USER_CREATE => 'ایجاد کاربر',
        self::USER_SHOW => 'مشاهده کاربر',
        self::USER_DELETE => 'حذف کاربران',
        self::USER_EDIT => 'ویرایش اطلاعات کاربران',
        self::USER_BLOCK => 'مسدودسازی کاربران',
        self::USER_UNBLOCK => 'رفع مسدودی کاربران',

        self::BANK => 'بانک ها',
        self::BANK_LIST => 'مشاهده لیست بانک ها',
        self::BANK_CREATE => 'ایجاد بانک',
        self::BANK_SHOW => 'مشاهده بانک',
        self::BANK_DELETE => 'حذف بانک',
        self::BANK_EDIT => 'ویرایش اطلاعات بانک',

        self::ORGAN => 'سازمان ها',
        self::ORGAN_LIST => 'مشاهده لیست سازمان ها',
        self::ORGAN_CREATE => 'ایجاد سازمان',
        self::ORGAN_SHOW => 'مشاهده سازمان',
        self::ORGAN_DELETE => 'حذف سازمان',
        self::ORGAN_EDIT => 'ویرایش اطلاعات سازمان',
        self::ORGAN_ASSIGN_ADMIN => 'افزودن یا حذف ادمین سازمان ها',

        self::ALLOCATION => 'بودجه/هزینه ها',
        self::ALLOCATION_LIST => 'مشاهده لیست بودجه/هزینه',
        self::ALLOCATION_CREATE => 'ایجاد بودجه/هزینه',
        self::ALLOCATION_SHOW => 'مشاهده بودجه/هزینه',
        self::ALLOCATION_DELETE => 'حذف بودجه/هزینه',
        self::ALLOCATION_EDIT => 'ویرایش بودجه/هزینه',

        self::DEPOSIT => 'حساب ها',
        self::DEPOSIT_LIST => 'مشاهده لیست حساب ها',
        self::DEPOSIT_CREATE => 'ایجاد حساب',
        self::DEPOSIT_SHOW => 'مشاهده حساب',
        self::DEPOSIT_DELETE => 'حذف حساب',
        self::DEPOSIT_EDIT => 'ویرایش اطلاعات حساب',

        self::PERMISSION => 'مجوز ها',
        self::PERMISSION_LIST => 'لیست مجوز ها',
        self::PERMISSION_EDIT => 'ویرایش مجوز',
        self::PERMISSION_SHOW => 'مشاهده مجوز',

        self::ROLE => 'نقش ها',
        self::ROLE_LIST => 'لیست نقش ها',
        self::ROLE_CREATE => 'ایجاد نقش',
        self::ROLE_EDIT => 'ویرایش نقش',
        self::ROLE_SHOW => 'مشاهده نقش',
        self::ROLE_DELETE => 'حذف نقش',

        self::ACCESS => 'دسترسی ها',
        self::ACCESS_LIST => 'لیست دسترسی ها',
        self::ACCESS_ASSIGN => 'مدیریت دسترسی',

        self::SETTING => 'تنظیمات',
        self::SETTING_LIST => 'مشاهده لیست تنظیمات',
        self::SETTING_EDIT => 'ویرایش تنظیمات',
        self::SETTING_SHOW => 'مشاهده تنظیمات',
        self::SETTING_CREATE => 'افزدون تنظیمات',
        self::SETTING_DELETE => 'حذف تنظیمات',

    ];
}
