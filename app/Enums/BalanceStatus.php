<?php

namespace App\Enums;

enum BalanceStatus: string
{
    case Fail = 'fail';
    case Success = 'success';
}
